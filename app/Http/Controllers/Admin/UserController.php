<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::with(['cliente', 'roles'])->get();

        return view('admin.usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nome_empresa')->get();

        return view('admin.usuarios.create', compact('clientes'));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'cliente_id' => ['required', 'exists:clientes,id'],
        ]);

        $usuario = User::create($dados);
        $usuario->assignRole('cliente');

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', "Usuário {$usuario->name} criado com sucesso.");
    }
}
