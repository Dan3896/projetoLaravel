<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoSla extends Model
{
    protected $table = 'historico_slas';

    protected $fillable = [
        'chamado_id',
        'status_id',
        'registrado_em',
    ];

    protected $casts = [
        'registrado_em' => 'datetime',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
