<?php

namespace App\Services;

use App\Models\Chamado;
use App\Models\PrazoSla;
use App\Models\Status;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SlaService
{
    /** @var Collection<string, int>|null prazos em horas por prioridade, carregados uma vez por requisição */
    private ?Collection $prazos = null;

    /**
     * Registra o status inicial do chamado no histórico de SLA.
     */
    public function registrarAbertura(Chamado $chamado): void
    {
        $chamado->historicosSla()->create([
            'status_id' => $chamado->status_id,
            'registrado_em' => $chamado->aberto_em ?? now(),
        ]);
    }

    /**
     * Troca o status do chamado, preenche os marcos de planejamento/dev/QA
     * e grava a mudança em historico_slas.
     */
    public function mudarStatus(Chamado $chamado, string $descricao, ?CarbonInterface $quando = null): void
    {
        $quando ??= now();
        $status = Status::where('descricao', $descricao)->firstOrFail();

        if ((int) $chamado->status_id === $status->id) {
            return;
        }

        DB::transaction(function () use ($chamado, $status, $quando) {
            $chamado->status()->associate($status);
            $this->preencherMarcos($chamado, $status->descricao, $quando);
            $chamado->save();

            $chamado->historicosSla()->create([
                'status_id' => $status->id,
                'registrado_em' => $quando,
            ]);
        });

        $chamado->unsetRelation('historicosSla');
    }

    /**
     * Calcula o tempo de atendimento em segundos.
     * "liquido" desconta os períodos em PENDENTE_CLIENTE; "pausado" é o tempo descontado.
     *
     * @return array{liquido: int, pausado: int}
     */
    public function calcular(Chamado $chamado, ?CarbonInterface $agora = null): array
    {
        $agora ??= now();
        $chamado->loadMissing('historicosSla.status');
        $historico = $chamado->historicosSla->values();

        // Chamados criados antes do histórico de SLA existir.
        if ($historico->isEmpty()) {
            $fim = $chamado->fechado_em ?? $agora;

            return ['liquido' => $this->segundosEntre($chamado->aberto_em, $fim), 'pausado' => 0];
        }

        $liquido = 0;
        $pausado = 0;

        foreach ($historico as $i => $registro) {
            $descricao = $registro->status->descricao;

            if (in_array($descricao, Status::ENCERRADOS, true)) {
                continue;
            }

            $fim = $historico[$i + 1]->registrado_em ?? $agora;
            $segundos = $this->segundosEntre($registro->registrado_em, $fim);

            if (in_array($descricao, Status::PAUSA_SLA, true)) {
                $pausado += $segundos;
            } else {
                $liquido += $segundos;
            }
        }

        return ['liquido' => $liquido, 'pausado' => $pausado];
    }

    /**
     * Prazo do chamado conforme a prioridade da triagem GUT (tabela prazos_sla).
     * Retorna null enquanto o chamado não foi triado ou a prioridade não tem prazo cadastrado.
     *
     * @param  array{liquido: int, pausado: int}|null  $tempo  resultado de calcular(), para não recalcular
     * @return array{horas: int, restante: int, atrasado: bool}|null
     */
    public function situacaoPrazo(Chamado $chamado, ?array $tempo = null): ?array
    {
        $chamado->loadMissing('triagem');

        if (! $chamado->triagem) {
            return null;
        }

        $this->prazos ??= PrazoSla::pluck('horas', 'prioridade');
        $horas = $this->prazos[$chamado->triagem->prioridade()] ?? null;

        if ($horas === null) {
            return null;
        }

        $tempo ??= $this->calcular($chamado);
        $restante = $horas * 3600 - $tempo['liquido'];

        return ['horas' => (int) $horas, 'restante' => $restante, 'atrasado' => $restante < 0];
    }

    public static function formatarDuracao(int $segundos): string
    {
        $minutos = intdiv($segundos, 60);
        $dias = intdiv($minutos, 1440);
        $horas = intdiv($minutos % 1440, 60);
        $minutos %= 60;

        if ($dias > 0) {
            return "{$dias}d {$horas}h";
        }

        if ($horas > 0) {
            return "{$horas}h {$minutos}min";
        }

        return "{$minutos}min";
    }

    private function preencherMarcos(Chamado $chamado, string $descricao, CarbonInterface $quando): void
    {
        switch ($descricao) {
            case 'EM_ANALISE':
                $chamado->inicio_planejamento ??= $quando;
                break;
            case 'APROVADO':
                if ($chamado->inicio_planejamento) {
                    $chamado->fim_planejamento ??= $quando;
                }
                break;
            case 'EM_DESENVOLVIMENTO':
                if ($chamado->inicio_planejamento) {
                    $chamado->fim_planejamento ??= $quando;
                }
                $chamado->inicio_dev ??= $quando;
                break;
            case 'EM_QA':
                if ($chamado->inicio_dev) {
                    $chamado->fim_dev ??= $quando;
                }
                $chamado->inicio_qa ??= $quando;
                break;
            case 'CONCLUIDO':
                if ($chamado->inicio_qa) {
                    $chamado->fim_qa ??= $quando;
                }
                break;
        }

        // Reabrir um chamado encerrado limpa a data de fechamento.
        $chamado->fechado_em = in_array($descricao, Status::ENCERRADOS, true) ? $quando : null;
    }

    private function segundosEntre(CarbonInterface $inicio, CarbonInterface $fim): int
    {
        return max(0, (int) $inicio->diffInSeconds($fim, false));
    }
}
