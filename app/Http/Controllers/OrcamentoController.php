<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrcamentoRequest;
use App\Http\Requests\ResponderOrcamentoRequest;
use App\Models\Chamado;
use App\Models\Orcamento;
use App\Services\OrcamentoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrcamentoController extends Controller
{
    /**
     * Cria o orçamento manualmente quando a abertura não gerou (empresa sem valor-hora, por exemplo).
     */
    public function store(Chamado $chamado, OrcamentoService $orcamentoService)
    {
        $orcamento = $orcamentoService->paraEdicao($chamado);

        return redirect()->route('chamados.orcamentos.edit', [$chamado, $orcamento]);
    }

    public function edit(Chamado $chamado, Orcamento $orcamento)
    {
        if (! $orcamento->editavel() || $chamado->estaEncerrado()) {
            return redirect()
                ->route('chamados.show', $chamado)
                ->with('error', 'Este orçamento não pode mais ser alterado.');
        }

        $chamado->load(['cliente.precoCliente', 'tipoChamado']);

        return view('orcamentos.edit', compact('chamado', 'orcamento'));
    }

    public function update(OrcamentoRequest $request, Chamado $chamado, Orcamento $orcamento, OrcamentoService $orcamentoService)
    {
        if (! $orcamento->editavel() || $chamado->estaEncerrado()) {
            return redirect()
                ->route('chamados.show', $chamado)
                ->with('error', 'Este orçamento não pode mais ser alterado.');
        }

        $enviar = $request->validated('acao') === 'enviar';

        // Uma transação só: se o envio falhar (ordem cancelada no meio), o ajuste também não é gravado.
        DB::transaction(function () use ($orcamentoService, $orcamento, $request, $enviar) {
            $orcamentoService->atualizar($orcamento, $request->safe()->except('acao'));

            if ($enviar) {
                $orcamentoService->enviar($orcamento, $request->user());
            }
        });

        if ($enviar) {
            return redirect()
                ->route('chamados.show', $chamado)
                ->with('success', 'Orçamento enviado ao cliente.');
        }

        return redirect()
            ->route('chamados.orcamentos.edit', [$chamado, $orcamento])
            ->with('success', 'Orçamento salvo.');
    }

    public function responder(ResponderOrcamentoRequest $request, Chamado $chamado, Orcamento $orcamento, OrcamentoService $orcamentoService)
    {
        $aprovado = $request->validated('decisao') === 'aprovar';
        $orcamentoService->responder($orcamento, $aprovado, $request->user(), $request->validated('motivo'));

        return redirect()
            ->route('chamados.show', $chamado)
            ->with('success', $aprovado ? 'Orçamento aprovado.' : 'Orçamento recusado.');
    }

    public function pdf(Chamado $chamado, Orcamento $orcamento)
    {
        Gate::authorize('verOrcamento', [$chamado, $orcamento]);

        $chamado->load(['cliente', 'tipoChamado']);

        return Pdf::loadView('orcamentos.pdf', compact('chamado', 'orcamento'))
            ->download("orcamento-os-{$chamado->id}.pdf");
    }
}
