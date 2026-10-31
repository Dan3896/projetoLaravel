<?php

namespace Database\Seeders;

use App\Models\PrazoSla;
use Illuminate\Database\Seeder;

class PrazoSlaSeeder extends Seeder
{
    /**
     * Prazos iniciais em horas de atendimento. São sugestões: o Márcio define os valores reais em /admin/prazos-sla.
     */
    public function run(): void
    {
        $prazos = [
            'Crítica' => 4,
            'Alta' => 8,
            'Média' => 24,
            'Baixa' => 72,
        ];

        foreach ($prazos as $prioridade => $horas) {
            PrazoSla::firstOrCreate(['prioridade' => $prioridade], ['horas' => $horas]);
        }
    }
}
