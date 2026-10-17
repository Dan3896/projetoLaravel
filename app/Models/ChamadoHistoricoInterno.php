<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamadoHistoricoInterno extends Model
{
    protected $table = 'chamado_historicos_internos';

    protected $fillable = [
        'chamado_id',
        'user_id',
        'texto',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
