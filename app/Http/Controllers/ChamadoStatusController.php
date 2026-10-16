<?php

namespace App\Http\Controllers;

use App\Http\Requests\AlterarStatusRequest;
use App\Models\Chamado;
use App\Services\SlaService;

class ChamadoStatusController extends Controller
{
    public function update(AlterarStatusRequest $request, Chamado $chamado, SlaService $sla)
    {
        $sla->mudarStatus($chamado, $request->validated('status'));

        return redirect()
            ->route('chamados.show', $chamado)
            ->with('success', 'Status atualizado para ' . $request->validated('status') . '.');
    }
}
