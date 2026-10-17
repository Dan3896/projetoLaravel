<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmpresaRequest;
use App\Models\Cliente;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresas = Cliente::withCount(['users', 'chamados'])->orderBy('nome_empresa')->get();

        return view('admin.empresas.index', compact('empresas'));
    }

    public function create()
    {
        return view('admin.empresas.form', ['empresa' => new Cliente()]);
    }

    public function store(EmpresaRequest $request)
    {
        $empresa = Cliente::create($request->validated());

        return redirect()->route('admin.empresas.index')->with('success', "Empresa {$empresa->nome_empresa} cadastrada.");
    }

    public function edit(Cliente $empresa)
    {
        return view('admin.empresas.form', compact('empresa'));
    }

    public function update(EmpresaRequest $request, Cliente $empresa)
    {
        $empresa->update($request->validated());

        return redirect()->route('admin.empresas.index')->with('success', "Empresa {$empresa->nome_empresa} atualizada.");
    }
}
