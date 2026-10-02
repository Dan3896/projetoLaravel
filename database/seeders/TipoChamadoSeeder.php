<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoChamado;

class TipoChamadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            'Defeito / Bug',
            'Melhoria / Nova Funcionalidade',
            'Dúvida / Suporte Técnico',
            'Consultoria / Ajuste Emergencial'
        ];

        foreach ($tipos as $tipo) {
            TipoChamado::firstOrCreate(['descricao' => $tipo]);
        }
    }
}
