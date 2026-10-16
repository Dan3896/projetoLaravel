<?php

namespace App\Models;

use App\Services\TriagemService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamadoTriagem extends Model
{
    protected $table = 'chamado_triagens';

    protected $fillable = [
        'chamado_id',
        'gut_gravidade',
        'gut_urgencia',
        'gut_tendencia',
        'pontuacao',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    public function prioridade(): string
    {
        return TriagemService::prioridade($this->pontuacao);
    }
}
