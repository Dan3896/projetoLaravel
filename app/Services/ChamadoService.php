<?php

namespace App\Services;

use App\Models\Chamado;
use App\Models\ChamadoAnexo;
use App\Models\ChamadoHistoricoInterno;
use App\Models\Etapa;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ChamadoService
{
    public function __construct(
        private SlaService $sla,
        private AnexoService $anexos,
        private OrcamentoService $orcamentos,
    ) {
    }

    /**
     * Abre o chamado no status ABERTO, na primeira etapa cadastrada, grava os anexos,
     * registra o início do SLA e gera o pré-orçamento. Se qualquer passo falhar, nada fica gravado.
     *
     * @param  array{cliente_id: int, tipo_chamado_id: int, descricao: string, criticidade_cliente: ?string}  $dados
     * @param  array<int, UploadedFile>  $anexos
     */
    public function abrir(array $dados, array $anexos = []): Chamado
    {
        $chamadoId = null;

        try {
            return DB::transaction(function () use ($dados, $anexos, &$chamadoId) {
                $chamado = Chamado::create([
                    'cliente_id' => $dados['cliente_id'],
                    'tipo_chamado_id' => $dados['tipo_chamado_id'],
                    'status_id' => Status::where('descricao', 'ABERTO')->firstOrFail()->id,
                    'etapa_id' => Etapa::ordenadas()->value('id'),
                    'descricao' => $dados['descricao'],
                    'criticidade_cliente' => $dados['criticidade_cliente'] ?? null,
                    'aberto_em' => now(),
                ]);
                $chamadoId = $chamado->id;

                $this->anexos->salvar($chamado, $anexos);
                $this->sla->registrarAbertura($chamado);
                $this->orcamentos->gerarPrevia($chamado);

                return $chamado;
            });
        } catch (Throwable $erro) {
            // A transação desfez os registros; sobram só os arquivos já gravados no disco.
            if ($chamadoId !== null) {
                Storage::disk(ChamadoAnexo::DISCO)->deleteDirectory('chamados/' . $chamadoId);
            }

            throw $erro;
        }
    }

    /**
     * @param  array{tipo_chamado_id: int, descricao: string, criticidade_cliente: ?string}  $dados
     */
    public function editar(Chamado $chamado, array $dados, User $autor): void
    {
        DB::transaction(function () use ($chamado, $dados, $autor) {
            $chamado->fill($dados);

            if (! $chamado->isDirty()) {
                return;
            }

            $campos = collect(array_keys($chamado->getDirty()))
                ->map(fn ($campo) => ['tipo_chamado_id' => 'tipo', 'descricao' => 'descrição', 'criticidade_cliente' => 'urgência'][$campo] ?? $campo)
                ->implode(', ');

            $chamado->save();

            $this->registrarHistoricoInterno($chamado, $autor, "Chamado editado ({$campos}).");
        });
    }

    public function alterarEtapa(Chamado $chamado, Etapa $etapa, User $autor): void
    {
        if ((int) $chamado->etapa_id === $etapa->id) {
            return;
        }

        DB::transaction(function () use ($chamado, $etapa, $autor) {
            $chamado->etapa()->associate($etapa);
            $chamado->save();

            $this->registrarHistoricoInterno($chamado, $autor, "Etapa alterada para \"{$etapa->nome}\".");
        });
    }

    public function cancelar(Chamado $chamado, User $autor, ?string $motivo = null): void
    {
        DB::transaction(function () use ($chamado, $autor, $motivo) {
            $this->sla->mudarStatus($chamado, 'CANCELADO');

            $quem = $autor->isAdmin() ? 'pela equipe' : 'pelo cliente';
            $texto = "Ordem cancelada {$quem}." . ($motivo ? " Motivo: {$motivo}" : '');

            $this->registrarHistoricoInterno($chamado, $autor, $texto);
        });
    }

    public function registrarHistoricoInterno(Chamado $chamado, ?User $autor, string $texto): ChamadoHistoricoInterno
    {
        return $chamado->historicosInternos()->create([
            'user_id' => $autor?->id,
            'texto' => $texto,
        ]);
    }

    /**
     * Posição da etapa atual entre as etapas cadastradas, para o "passo x de y" do cliente.
     *
     * @return array{etapas: \Illuminate\Support\Collection<int, Etapa>, atual: int, total: int}
     */
    public function progresso(Chamado $chamado): array
    {
        $etapas = Etapa::ordenadas()->get();
        $indice = $etapas->search(fn (Etapa $etapa) => $etapa->id === (int) $chamado->etapa_id);

        // Chamado concluído conta como todas as etapas cumpridas.
        $atual = $chamado->status->descricao === 'CONCLUIDO'
            ? $etapas->count()
            : ($indice === false ? 0 : $indice + 1);

        return ['etapas' => $etapas, 'atual' => $atual, 'total' => $etapas->count()];
    }
}
