<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function precoCliente(): HasOne
    {
        return $this->hasOne(PrecoCliente::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
