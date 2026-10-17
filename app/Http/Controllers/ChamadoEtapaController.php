<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlterarEtapaRequest;
use App\Models\Chamado;
use App\Models\Etapa;
use App\Services\ChamadoService;

class ChamadoEtapaController extends Controller
{
    public function update(AlterarEtapaRequest $request, Chamado $chamado, ChamadoService $chamadoService)
    {
        $etapa = Etapa::findOrFail($request->validated('etapa_id'));

        $chamadoService->alterarEtapa($chamado, $etapa, $request->user());

        return redirect()
            ->route('chamados.show', $chamado)
            ->with('success', "Etapa atualizada para {$etapa->nome}.");
    }
}
