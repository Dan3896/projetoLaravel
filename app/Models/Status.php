<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    public const ENCERRADOS = ['CONCLUIDO', 'CANCELADO'];

    // Enquanto o chamado está nestes status, a contagem do SLA fica pausada.
    public const PAUSA_SLA = ['PENDENTE_CLIENTE'];

    protected $table = 'status';

    protected $fillable = [
        'descricao',
    ];

    public function chamados(): HasMany
    {
        return $this->hasMany(Chamado::class, 'status_id');
    }
}
