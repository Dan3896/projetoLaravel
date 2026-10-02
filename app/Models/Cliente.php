<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $fillable = [
        'nome_empresa',
        'perfil',
    ];

    public function chamados(): HasMany
    {
        return $this->hasMany(Chamado::class);
    }
}
