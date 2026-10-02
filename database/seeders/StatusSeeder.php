<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'ABERTO',
            'EM_TRIAGEM',
            'EM_ANALISE',
            'PENDENTE_CLIENTE',
            'ORCAMENTO_GERADO',
            'APROVADO',
            'EM_DESENVOLVIMENTO',
            'EM_QA',
            'CONCLUIDO',
            'CANCELADO'
        ];

        foreach ($statuses as $status) {
            Status::firstOrCreate(['descricao' => $status]);
        }
    }
}
