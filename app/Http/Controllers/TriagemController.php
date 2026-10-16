<?php

namespace App\Http\Controllers;

use App\Http\Requests\TriagemRequest;
use App\Models\Chamado;
use App\Models\TipoChamado;
use App\Services\TriagemService;

class TriagemController extends Controller
{
    public function create(Chamado $chamado)
    {
        $chamado->load(['cliente', 'status', 'tipoChamado', 'triagem']);

        if ($chamado->estaEncerrado()) {
            return redirect()
                ->route('chamados.show', $chamado)
                ->with('error', 'Chamados concluídos ou cancelados não podem ser triados.');
        }

        $tipos = TipoChamado::all();
        $proximosStatus = TriagemService::PROXIMOS_STATUS;

        return view('chamados.triagem', compact('chamado', 'tipos', 'proximosStatus'));
    }

    public function store(TriagemRequest $request, Chamado $chamado, TriagemService $triagemService)
    {
        if ($chamado->estaEncerrado()) {
            return redirect()
                ->route('chamados.show', $chamado)
                ->with('error', 'Chamados concluídos ou cancelados não podem ser triados.');
        }

        $triagem = $triagemService->registrar($chamado, $request->validated());

        return redirect()
            ->route('chamados.show', $chamado)
            ->with('success', "Triagem registrada: {$triagem->pontuacao} pontos (prioridade {$triagem->prioridade()}).");
    }
}
