<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecoCliente extends Model
{
    protected $table = 'preco_clientes';

    protected $fillable = [
        'cliente_id',
        'valor_hora',
    ];

    protected $casts = [
        'valor_hora' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
