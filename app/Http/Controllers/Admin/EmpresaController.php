<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmpresaRequest;
use App\Models\Cliente;
use Illuminate\Support\Facades\DB;

class EmpresaController extends Controller
{
    public function index()
    {
        $empresas = Cliente::with('precoCliente')->withCount(['users', 'chamados'])->orderBy('nome_empresa')->get();

        return view('admin.empresas.index', compact('empresas'));
    }

    public function create()
    {
        return view('admin.empresas.form', ['empresa' => new Cliente()]);
    }

    public function store(EmpresaRequest $request)
    {
        $empresa = DB::transaction(function () use ($request) {
            $empresa = Cliente::create($request->safe()->only(['nome_empresa', 'perfil']));
            $this->salvarValorHora($empresa, $request->validated('valor_hora'));

            return $empresa;
        });

        return redirect()->route('admin.empresas.index')->with('success', "Empresa {$empresa->nome_empresa} cadastrada.");
    }

    public function edit(Cliente $empresa)
    {
        $empresa->load('precoCliente');

        return view('admin.empresas.form', compact('empresa'));
    }

    public function update(EmpresaRequest $request, Cliente $empresa)
    {
        DB::transaction(function () use ($request, $empresa) {
            $empresa->update($request->safe()->only(['nome_empresa', 'perfil']));
            $this->salvarValorHora($empresa, $request->validated('valor_hora'));
        });

        return redirect()->route('admin.empresas.index')->with('success', "Empresa {$empresa->nome_empresa} atualizada.");
    }

    private function salvarValorHora(Cliente $empresa, $valorHora): void
    {
        if ($valorHora === null || $valorHora === '') {
            $empresa->precoCliente()->delete();

            return;
        }

        $empresa->precoCliente()->updateOrCreate([], ['valor_hora' => $valorHora]);
    }
}
