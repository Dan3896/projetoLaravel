<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Orcamento extends Model
{
    public const PENDENTE = 'pendente';

    public const ENVIADO = 'enviado';

    public const APROVADO = 'aprovado';

    public const RECUSADO = 'recusado';

    public const COMPONENTES = [
        'planejamento' => 'Planejamento',
        'desenvolvimento' => 'Desenvolvimento',
        'qa' => 'QA / Testes',
    ];

    protected $fillable = [
        'chamado_id',
        'status',
        'valor_hora',
        'horas_planejamento',
        'horas_desenvolvimento',
        'horas_qa',
        'valor_total',
        'observacoes',
        'motivo_recusa',
        'link_documento',
        'enviado_em',
        'respondido_em',
    ];

    protected $casts = [
        'valor_hora' => 'decimal:2',
        'horas_planejamento' => 'decimal:2',
        'horas_desenvolvimento' => 'decimal:2',
        'horas_qa' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'enviado_em' => 'datetime',
        'respondido_em' => 'datetime',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    /**
     * Horas e valor de cada componente (planejamento, desenvolvimento e QA).
     *
     * @return array<string, array{rotulo: string, horas: float, valor: float}>
     */
    public function componentes(): array
    {
        $valorHora = (float) $this->valor_hora;

        return collect(self::COMPONENTES)
            ->map(fn ($rotulo, $chave) => [
                'rotulo' => $rotulo,
                'horas' => (float) $this->{"horas_{$chave}"},
                'valor' => round((float) $this->{"horas_{$chave}"} * $valorHora, 2),
            ])
            ->all();
    }

    public function totalHoras(): float
    {
        return (float) $this->horas_planejamento + (float) $this->horas_desenvolvimento + (float) $this->horas_qa;
    }

    public function editavel(): bool
    {
        return in_array($this->status, [self::PENDENTE, self::RECUSADO], true);
    }

    /**
     * O cliente só enxerga o orçamento depois que o Márcio envia.
     */
    public function visivelParaCliente(): bool
    {
        return $this->status !== self::PENDENTE;
    }

    public static function formatarMoeda(float|string|null $valor): string
    {
        return 'R$ ' . number_format((float) $valor, 2, ',', '.');
    }

    public static function formatarHoras(float|string|null $horas): string
    {
        return rtrim(rtrim(number_format((float) $horas, 2, ',', '.'), '0'), ',') . 'h';
    }
}
