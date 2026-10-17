<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelarChamadoRequest;
use App\Models\Chamado;
use App\Services\ChamadoService;

class ChamadoCancelamentoController extends Controller
{
    public function store(CancelarChamadoRequest $request, Chamado $chamado, ChamadoService $chamadoService)
    {
        $chamadoService->cancelar($chamado, $request->user(), $request->validated('motivo'));

        return redirect()
            ->route('chamados.show', $chamado)
            ->with('success', "Ordem de serviço #{$chamado->id} cancelada.");
    }
}
