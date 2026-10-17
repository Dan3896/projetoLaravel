<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etapa extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'ordem',
    ];

    public function chamados(): HasMany
    {
        return $this->hasMany(Chamado::class);
    }

    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderBy('ordem')->orderBy('id');
    }
}
