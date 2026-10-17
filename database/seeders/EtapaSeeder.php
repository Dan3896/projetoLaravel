<?php

namespace Database\Seeders;

use App\Models\Etapa;
use Illuminate\Database\Seeder;

class EtapaSeeder extends Seeder
{
    /**
     * Etapas iniciais. O Márcio pode renomear, reordenar ou criar outras em /admin/etapas.
     */
    public function run(): void
    {
        $etapas = [
            ['nome' => 'Recebido', 'descricao' => 'Recebemos o seu chamado.'],
            ['nome' => 'Triagem', 'descricao' => 'Estamos avaliando a prioridade e o escopo.'],
            ['nome' => 'Em andamento', 'descricao' => 'A equipe está trabalhando na solução.'],
            ['nome' => 'Em testes', 'descricao' => 'A solução está sendo testada.'],
            ['nome' => 'Concluído', 'descricao' => 'O chamado foi resolvido.'],
        ];

        foreach ($etapas as $ordem => $etapa) {
            Etapa::firstOrCreate(['nome' => $etapa['nome']], $etapa + ['ordem' => $ordem + 1]);
        }
    }
}
