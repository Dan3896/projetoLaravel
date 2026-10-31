<?php

namespace App\Services;

use App\Models\Chamado;
use App\Models\ChamadoHistoricoInterno;
use App\Models\Orcamento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class OrcamentoService
{
    public function __construct(private SlaService $sla)
    {
    }

    /**
     * RF-04: na abertura do chamado, gera o pré-orçamento pendente com as horas do tipo de chamado
     * e o valor-hora da empresa. Sem valor-hora ou sem horas cadastradas, não gera nada.
     */
    public function gerarPrevia(Chamado $chamado): ?Orcamento
    {
        $chamado->loadMissing(['cliente.precoCliente', 'tipoChamado']);
        $valorHora = $chamado->cliente->precoCliente?->valor_hora;

        if ($valorHora === null || $chamado->tipoChamado->totalHoras() <= 0) {
            return null;
        }

        return $this->criar($chamado);
    }

    /**
     * Cria um orçamento pendente a partir do catálogo, mesmo sem valor-hora (o Márcio completa na edição).
     */
    public function criar(Chamado $chamado): Orcamento
    {
        $chamado->loadMissing(['cliente.precoCliente', 'tipoChamado']);
        $tipo = $chamado->tipoChamado;

        $orcamento = new Orcamento([
            'status' => Orcamento::PENDENTE,
            'valor_hora' => $chamado->cliente->precoCliente?->valor_hora ?? 0,
            'horas_planejamento' => $tipo->horas_planejamento,
            'horas_desenvolvimento' => $tipo->horas_desenvolvimento,
            'horas_qa' => $tipo->horas_qa,
        ]);
        $orcamento->valor_total = self::calcularTotal($orcamento);

        $chamado->orcamentos()->save($orcamento);
        $chamado->unsetRelation('orcamento');

        return $orcamento;
    }

    /**
     * Ajuste do Márcio antes de enviar. Editar um orçamento recusado o devolve para pendente.
     *
     * @param  array{valor_hora: float, horas_planejamento: float, horas_desenvolvimento: float, horas_qa: float, observacoes: ?string}  $dados
     */
    public function atualizar(Orcamento $orcamento, array $dados): void
    {
        if (! $orcamento->editavel()) {
            throw new LogicException('Só orçamentos pendentes ou recusados podem ser alterados.');
        }

        $orcamento->fill($dados);
        $orcamento->status = Orcamento::PENDENTE;
        $orcamento->motivo_recusa = null;
        $orcamento->valor_total = self::calcularTotal($orcamento);
        $orcamento->save();
    }

    public function enviar(Orcamento $orcamento, User $autor): void
    {
        if (! $orcamento->editavel()) {
            throw new LogicException('Este orçamento já foi enviado.');
        }

        DB::transaction(function () use ($orcamento, $autor) {
            $orcamento->update([
                'status' => Orcamento::ENVIADO,
                'enviado_em' => now(),
                'respondido_em' => null,
            ]);

            $this->sla->mudarStatus($orcamento->chamado, 'ORCAMENTO_GERADO');
            $this->registrar($orcamento, $autor, 'Orçamento enviado ao cliente: ' . Orcamento::formatarMoeda($orcamento->valor_total) . '.');
        });
    }

    public function responder(Orcamento $orcamento, bool $aprovado, User $autor, ?string $motivo = null): void
    {
        if ($orcamento->status !== Orcamento::ENVIADO) {
            throw new LogicException('Só orçamentos enviados podem ser aprovados ou recusados.');
        }

        DB::transaction(function () use ($orcamento, $aprovado, $autor, $motivo) {
            $orcamento->update([
                'status' => $aprovado ? Orcamento::APROVADO : Orcamento::RECUSADO,
                'motivo_recusa' => $aprovado ? null : $motivo,
                'respondido_em' => now(),
            ]);

            $quem = $autor->isAdmin() ? 'pela equipe' : 'pelo cliente';

            if ($aprovado) {
                $this->sla->mudarStatus($orcamento->chamado, 'APROVADO');
                $this->registrar($orcamento, $autor, "Orçamento aprovado {$quem}.");
            } else {
                $this->registrar($orcamento, $autor, "Orçamento recusado {$quem}." . ($motivo ? " Motivo: {$motivo}" : ''));
            }
        });
    }

    public static function calcularTotal(Orcamento $orcamento): float
    {
        // Soma dos componentes já arredondados, para o total bater com as linhas do PDF.
        return round(collect($orcamento->componentes())->sum('valor'), 2);
    }

    private function registrar(Orcamento $orcamento, User $autor, string $texto): ChamadoHistoricoInterno
    {
        return $orcamento->chamado->historicosInternos()->create([
            'user_id' => $autor->id,
            'texto' => $texto,
        ]);
    }
}
