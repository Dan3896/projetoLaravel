<?php

namespace App\Http\Controllers;

use App\Models\Chamado;
use App\Models\Cliente;
use App\Models\Status;
use App\Models\TipoChamado;
use Illuminate\Http\Request;

class ChamadoController extends Controller
{
    public function index()
    {
        $chamados = Chamado::with(['cliente', 'status', 'tipoChamado'])->latest()->get();
        return view('chamados.index', compact('chamados'));
    }

    public function create()
    {
        $clientes = Cliente::all();
        $tipos = TipoChamado::all();
        $statusAberto = Status::where('descricao', 'ABERTO')->first();

        return view('chamados.create', compact('clientes', 'tipos', 'statusAberto'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_chamado_id' => 'required|exists:tipo_chamados,id',
            'descricao' => 'required|string|min:10',
            'criticidade_cliente' => 'nullable|string|in:Baixa,Média,Alta,Crítica',
        ]);

        $statusAberto = Status::where('descricao', 'ABERTO')->first();

        Chamado::create([
            'cliente_id' => $validated['cliente_id'],
            'tipo_chamado_id' => $validated['tipo_chamado_id'],
            'status_id' => $statusAberto->id,
            'descricao' => $validated['descricao'],
            'criticidade_cliente' => $validated['criticidade_cliente'],
            'aberto_em' => now(),
        ]);

        return redirect()->route('chamados.index')->with('success', 'Chamado/OS aberto com sucesso!');
    }

    public function show(Chamado $chamado)
    {
        $chamado->load(['cliente', 'status', 'tipoChamado']);
        return view('chamados.show', compact('chamado'));
    }
}
