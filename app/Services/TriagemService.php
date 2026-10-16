<?php

namespace App\Services;

use App\Models\Chamado;
use App\Models\ChamadoTriagem;
use Illuminate\Support\Facades\DB;

class TriagemService
{
    // Status para onde o Márcio pode mandar o chamado ao concluir a triagem.
    public const PROXIMOS_STATUS = [
        'EM_TRIAGEM' => 'Continuar em triagem',
        'EM_ANALISE' => 'Enviar para análise / orçamento',
        'PENDENTE_CLIENTE' => 'Pedir mais informações ao cliente',
        'CANCELADO' => 'Cancelar chamado',
    ];

    public const PRIORIDADES = ['Crítica', 'Alta', 'Média', 'Baixa'];

    public function __construct(private SlaService $sla)
    {
    }

    public static function calcularPontuacao(int $gravidade, int $urgencia, int $tendencia): int
    {
        return $gravidade * $urgencia * $tendencia;
    }

    public static function prioridade(?int $pontuacao): string
    {
        return match (true) {
            $pontuacao === null => 'Não triado',
            $pontuacao <= 24 => 'Baixa',
            $pontuacao <= 60 => 'Média',
            $pontuacao <= 99 => 'Alta',
            default => 'Crítica',
        };
    }

    /**
     * Grava a matriz GUT, corrige o tipo do chamado e move o chamado para o próximo status.
     *
     * @param  array{gut_gravidade: int, gut_urgencia: int, gut_tendencia: int, tipo_chamado_id: int, proximo_status: string}  $dados
     */
    public function registrar(Chamado $chamado, array $dados): ChamadoTriagem
    {
        return DB::transaction(function () use ($chamado, $dados) {
            $triagem = $chamado->triagem()->updateOrCreate([], [
                'gut_gravidade' => $dados['gut_gravidade'],
                'gut_urgencia' => $dados['gut_urgencia'],
                'gut_tendencia' => $dados['gut_tendencia'],
                'pontuacao' => self::calcularPontuacao(
                    $dados['gut_gravidade'],
                    $dados['gut_urgencia'],
                    $dados['gut_tendencia'],
                ),
            ]);

            $chamado->tipo_chamado_id = $dados['tipo_chamado_id'];
            $chamado->save();

            $this->sla->mudarStatus($chamado, $dados['proximo_status']);

            return $triagem;
        });
    }
}
