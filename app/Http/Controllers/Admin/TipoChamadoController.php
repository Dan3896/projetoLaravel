<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TipoChamadoRequest;
use App\Models\TipoChamado;

class TipoChamadoController extends Controller
{
    public function index()
    {
        $tipos = TipoChamado::orderBy('descricao')->get();

        return view('admin.tipos-chamado.index', compact('tipos'));
    }

    public function create()
    {
        return view('admin.tipos-chamado.form', ['tipo' => new TipoChamado()]);
    }

    public function store(TipoChamadoRequest $request)
    {
        $tipo = TipoChamado::create($request->validated());

        return redirect()->route('admin.tipos-chamado.index')->with('success', "Tipo {$tipo->descricao} criado.");
    }

    public function edit(TipoChamado $tipo)
    {
        return view('admin.tipos-chamado.form', compact('tipo'));
    }

    public function update(TipoChamadoRequest $request, TipoChamado $tipo)
    {
        $tipo->update($request->validated());

        return redirect()->route('admin.tipos-chamado.index')->with('success', "Tipo {$tipo->descricao} atualizado.");
    }
}
