<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChamadoRequest;
use App\Models\Chamado;
use App\Models\Cliente;
use App\Models\Status;
use App\Models\TipoChamado;
use App\Services\ChamadoService;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChamadoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $chamados = Chamado::with(['cliente', 'status', 'etapa', 'tipoChamado', 'triagem'])
            ->when(! $user->isAdmin(), fn ($query) => $query->where('cliente_id', $user->cliente_id))
            ->latest()
            ->get();

        return view('chamados.index', compact('chamados'));
    }

    public function create()
    {
        Gate::authorize('create', Chamado::class);

        $clientes = Cliente::orderBy('nome_empresa')->get();
        $tipos = TipoChamado::all();

        return view('chamados.create', compact('clientes', 'tipos'));
    }

    public function store(StoreChamadoRequest $request, ChamadoService $chamadoService)
    {
        $dados = $request->validated();

        // O cliente só abre chamado para a própria empresa; o admin escolhe a empresa no formulário.
        if (! $request->user()->isAdmin()) {
            $dados['cliente_id'] = $request->user()->cliente_id;
        }

        $chamadoService->abrir($dados);

        return redirect()->route('chamados.index')->with('success', 'Chamado/OS aberto com sucesso!');
    }

    public function show(Request $request, Chamado $chamado, SlaService $sla, ChamadoService $chamadoService)
    {
        Gate::authorize('view', $chamado);

        $chamado->load(['cliente', 'status', 'etapa', 'tipoChamado', 'triagem', 'historicosSla.status', 'historicosInternos.autor']);
        $progresso = $chamadoService->progresso($chamado);

        if (! $request->user()->isAdmin()) {
            return view('chamados.show', compact('chamado', 'progresso'));
        }

        $tempoSla = $sla->calcular($chamado);
        $statuses = Status::orderBy('id')->get();
        $etapas = $progresso['etapas'];

        return view('chamados.show', compact('chamado', 'progresso', 'tempoSla', 'statuses', 'etapas'));
    }
}
