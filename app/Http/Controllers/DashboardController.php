<?php

namespace App\Http\Controllers;

use App\Models\Chamado;
use App\Models\Status;
use App\Services\SlaService;
use App\Services\TriagemService;

class DashboardController extends Controller
{
    public function __invoke(SlaService $sla)
    {
        $emAberto = Chamado::with(['cliente', 'status', 'tipoChamado', 'triagem', 'historicosSla.status'])
            ->whereHas('status', fn ($query) => $query->whereNotIn('descricao', Status::ENCERRADOS))
            ->get()
            ->each(fn (Chamado $chamado) => $chamado->sla = $sla->calcular($chamado));

        $aguardandoTriagem = $emAberto
            ->whereNull('triagem')
            ->sortBy('aberto_em')
            ->values();

        $fila = $emAberto
            ->whereNotNull('triagem')
            ->sortBy([['triagem.pontuacao', 'desc'], ['aberto_em', 'asc']])
            ->values();

        $porStatus = Status::withCount('chamados')->orderBy('id')->get();

        $porPrioridade = collect(TriagemService::PRIORIDADES)
            ->mapWithKeys(fn ($prioridade) => [
                $prioridade => $fila->filter(fn ($chamado) => $chamado->triagem->prioridade() === $prioridade)->count(),
            ]);

        $indicadores = [
            'Em aberto' => $emAberto->count(),
            'Aguardando triagem' => $aguardandoTriagem->count(),
            'Críticos' => $porPrioridade['Crítica'],
            'Pendentes do cliente' => $emAberto->filter(fn ($chamado) => in_array($chamado->status->descricao, Status::PAUSA_SLA, true))->count(),
        ];

        return view('dashboard', compact('aguardandoTriagem', 'fila', 'porStatus', 'porPrioridade', 'indicadores'));
    }
}
