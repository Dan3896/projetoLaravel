<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoChamado;

class TipoChamadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Horas estimadas iniciais (planejamento / desenvolvimento / QA); o Márcio ajusta em /admin/tipos-chamado.
     */
    public function run(): void
    {
        $tipos = [
            'Defeito / Bug' => [0.5, 2, 1],
            'Melhoria / Nova Funcionalidade' => [2, 6, 2],
            'Dúvida / Suporte Técnico' => [0, 1, 0],
            'Consultoria / Ajuste Emergencial' => [1, 4, 1],
        ];

        foreach ($tipos as $tipo => [$planejamento, $desenvolvimento, $qa]) {
            TipoChamado::firstOrCreate(['descricao' => $tipo], [
                'horas_planejamento' => $planejamento,
                'horas_desenvolvimento' => $desenvolvimento,
                'horas_qa' => $qa,
            ]);
        }
    }
}
