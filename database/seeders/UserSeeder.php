<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Cliente;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@oicram.local'],
            ['name' => 'Administrador', 'password' => 'password']
        );
        $admin->assignRole('admin');

        $cliente = Cliente::where('nome_empresa', 'Empresa Teste SA')->first();

        $usuarioCliente = User::firstOrCreate(
            ['email' => 'cliente@empresa.com'],
            [
                'name' => 'Cliente Teste',
                'password' => 'password',
                'cliente_id' => $cliente?->id,
            ]
        );
        $usuarioCliente->assignRole('cliente');
    }
}
