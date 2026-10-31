<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\PrecoCliente;
use Illuminate\Database\Seeder;

class PrecoClienteSeeder extends Seeder
{
    /**
     * Valor-hora de teste para a empresa de homologação.
     */
    public function run(): void
    {
        $cliente = Cliente::where('nome_empresa', 'Empresa Teste SA')->first();

        if ($cliente) {
            PrecoCliente::firstOrCreate(['cliente_id' => $cliente->id], ['valor_hora' => 150]);
        }
    }
}
