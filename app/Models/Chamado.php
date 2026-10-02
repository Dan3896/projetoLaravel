<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chamado extends Model
{
    protected $fillable = [
        'cliente_id',
        'status_id',
        'tipo_chamado_id',
        'descricao',
        'criticidade_cliente',
        'aberto_em',
        'inicio_planejamento',
        'fim_planejamento',
        'inicio_dev',
        'fim_dev',
        'inicio_qa',
        'fim_qa',
        'fechado_em',
    ];

    protected $casts = [
        'aberto_em' => 'datetime',
        'inicio_planejamento' => 'datetime',
        'fim_planejamento' => 'datetime',
        'inicio_dev' => 'datetime',
        'fim_dev' => 'datetime',
        'inicio_qa' => 'datetime',
        'fim_qa' => 'datetime',
        'fechado_em' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function tipoChamado(): BelongsTo
    {
        return $this->belongsTo(TipoChamado::class, 'tipo_chamado_id');
    }
}
