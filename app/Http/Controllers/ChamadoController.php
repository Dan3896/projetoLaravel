<?php

namespace App\Http\Controllers;

use App\Models\Chamado;
use App\Models\Cliente;
use App\Models\Status;
use App\Models\TipoChamado;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChamadoController extends Controller
{
    public function index()
    {
        $chamados = Chamado::with(['cliente', 'status', 'tipoChamado', 'triagem'])->latest()->get();
        return view('chamados.index', compact('chamados'));
    }

    public function create()
    {
        $clientes = Cliente::all();
        $tipos = TipoChamado::all();
        $statusAberto = Status::where('descricao', 'ABERTO')->first();

        return view('chamados.create', compact('clientes', 'tipos', 'statusAberto'));
    }

    public function store(Request $request, SlaService $sla)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_chamado_id' => 'required|exists:tipo_chamados,id',
            'descricao' => 'required|string|min:10',
            'criticidade_cliente' => 'nullable|string|in:Baixa,Média,Alta,Crítica',
        ]);

        $statusAberto = Status::where('descricao', 'ABERTO')->first();

        DB::transaction(function () use ($validated, $statusAberto, $sla) {
            $chamado = Chamado::create([
                'cliente_id' => $validated['cliente_id'],
                'tipo_chamado_id' => $validated['tipo_chamado_id'],
                'status_id' => $statusAberto->id,
                'descricao' => $validated['descricao'],
                'criticidade_cliente' => $validated['criticidade_cliente'],
                'aberto_em' => now(),
            ]);

            $sla->registrarAbertura($chamado);
        });

        return redirect()->route('chamados.index')->with('success', 'Chamado/OS aberto com sucesso!');
    }

    public function show(Chamado $chamado, SlaService $sla)
    {
        $chamado->load(['cliente', 'status', 'tipoChamado', 'triagem', 'historicosSla.status']);
        $tempoSla = $sla->calcular($chamado);
        $statuses = Status::orderBy('id')->get();

        return view('chamados.show', compact('chamado', 'tempoSla', 'statuses'));
    }
}
