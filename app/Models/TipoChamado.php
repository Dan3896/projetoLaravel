<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoChamado extends Model
{
    protected $fillable = [
        'descricao',
        'horas_planejamento',
        'horas_desenvolvimento',
        'horas_qa',
    ];

    protected $casts = [
        'horas_planejamento' => 'decimal:2',
        'horas_desenvolvimento' => 'decimal:2',
        'horas_qa' => 'decimal:2',
    ];

    public function totalHoras(): float
    {
        return (float) $this->horas_planejamento + (float) $this->horas_desenvolvimento + (float) $this->horas_qa;
    }
}
