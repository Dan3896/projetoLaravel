<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrazoSla extends Model
{
    protected $table = 'prazos_sla';

    protected $fillable = [
        'prioridade',
        'horas',
    ];

    protected $casts = [
        'horas' => 'integer',
    ];
}
