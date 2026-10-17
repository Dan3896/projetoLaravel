<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EtapaRequest;
use App\Models\Etapa;

class EtapaController extends Controller
{
    public function index()
    {
        $etapas = Etapa::ordenadas()->withCount('chamados')->get();

        return view('admin.etapas.index', compact('etapas'));
    }

    public function create()
    {
        $etapa = new Etapa(['ordem' => (int) Etapa::max('ordem') + 1]);

        return view('admin.etapas.form', compact('etapa'));
    }

    public function store(EtapaRequest $request)
    {
        $etapa = Etapa::create($request->validated());

        return redirect()->route('admin.etapas.index')->with('success', "Etapa {$etapa->nome} criada.");
    }

    public function edit(Etapa $etapa)
    {
        return view('admin.etapas.form', compact('etapa'));
    }

    public function update(EtapaRequest $request, Etapa $etapa)
    {
        $etapa->update($request->validated());

        return redirect()->route('admin.etapas.index')->with('success', "Etapa {$etapa->nome} atualizada.");
    }

    public function destroy(Etapa $etapa)
    {
        if ($etapa->chamados()->exists()) {
            return redirect()
                ->route('admin.etapas.index')
                ->with('error', "A etapa {$etapa->nome} está em uso por chamados e não pode ser excluída.");
        }

        $etapa->delete();

        return redirect()->route('admin.etapas.index')->with('success', "Etapa {$etapa->nome} excluída.");
    }
}
