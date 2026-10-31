<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChamadoRequest;
use App\Http\Requests\UpdateChamadoRequest;
use App\Models\Chamado;
use App\Models\Cliente;
use App\Models\Etapa;
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
        $filtros = $request->only(['busca', 'status', 'etapa', 'empresa']);

        $chamados = Chamado::with(['cliente', 'status', 'etapa', 'tipoChamado', 'triagem'])
            ->when(! $user->isAdmin(), fn ($query) => $query->where('cliente_id', $user->cliente_id))
            ->when($user->isAdmin() && ! empty($filtros['empresa']), fn ($query) => $query->where('cliente_id', $filtros['empresa']))
            ->when($user->isAdmin() && ! empty($filtros['status']), fn ($query) => $query->where('status_id', $filtros['status']))
            ->when(! empty($filtros['etapa']), fn ($query) => $query->where('etapa_id', $filtros['etapa']))
            ->when(! empty($filtros['busca']), function ($query) use ($filtros) {
                $busca = trim($filtros['busca']);

                $query->where(function ($query) use ($busca) {
                    $query->where('descricao', 'like', "%{$busca}%")
                        ->orWhereHas('cliente', fn ($cliente) => $cliente->where('nome_empresa', 'like', "%{$busca}%"));

                    if (ctype_digit(ltrim($busca, '#'))) {
                        $query->orWhere('id', (int) ltrim($busca, '#'));
                    }
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $statuses = $user->isAdmin() ? Status::orderBy('id')->get() : collect();
        $etapas = Etapa::ordenadas()->get();
        $empresas = $user->isAdmin() ? Cliente::orderBy('nome_empresa')->get() : collect();

        return view('chamados.index', compact('chamados', 'filtros', 'statuses', 'etapas', 'empresas'));
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
        $dados = $request->safe()->except('anexos');

        // O cliente só abre chamado para a própria empresa; o admin escolhe a empresa no formulário.
        if (! $request->user()->isAdmin()) {
            $dados['cliente_id'] = $request->user()->cliente_id;
        }

        $chamado = $chamadoService->abrir($dados, $request->file('anexos', []));

        return redirect()
            ->route('chamados.show', $chamado)
            ->with('success', "Chamado/OS #{$chamado->id} aberto com sucesso!");
    }

    public function show(Request $request, Chamado $chamado, SlaService $sla, ChamadoService $chamadoService)
    {
        Gate::authorize('view', $chamado);

        $chamado->load([
            'cliente.precoCliente', 'status', 'etapa', 'tipoChamado', 'triagem', 'anexos', 'orcamento',
            'historicosSla.status', 'historicosInternos.autor',
        ]);
        $progresso = $chamadoService->progresso($chamado);

        if (! $request->user()->isAdmin()) {
            return view('chamados.show', compact('chamado', 'progresso'));
        }

        $tempoSla = $sla->calcular($chamado);
        $prazo = $sla->situacaoPrazo($chamado, $tempoSla);
        $statuses = Status::orderBy('id')->get();
        $etapas = $progresso['etapas'];

        return view('chamados.show', compact('chamado', 'progresso', 'tempoSla', 'prazo', 'statuses', 'etapas'));
    }

    public function edit(Chamado $chamado)
    {
        $tipos = TipoChamado::all();

        return view('chamados.edit', compact('chamado', 'tipos'));
    }

    public function update(UpdateChamadoRequest $request, Chamado $chamado, ChamadoService $chamadoService)
    {
        $chamadoService->editar($chamado, $request->validated(), $request->user());

        return redirect()
            ->route('chamados.show', $chamado)
            ->with('success', "OS #{$chamado->id} atualizada.");
    }
}
