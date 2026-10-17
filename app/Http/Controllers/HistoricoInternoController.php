<?php

namespace App\Http\Controllers;

use App\Http\Requests\HistoricoInternoRequest;
use App\Models\Chamado;
use App\Services\ChamadoService;

class HistoricoInternoController extends Controller
{
    public function store(HistoricoInternoRequest $request, Chamado $chamado, ChamadoService $chamadoService)
    {
        $chamadoService->registrarHistoricoInterno($chamado, $request->user(), $request->validated('texto'));

        return redirect()
            ->to(route('chamados.show', $chamado) . '#historico-interno')
            ->with('success', 'Anotação registrada no histórico interno.');
    }
}
