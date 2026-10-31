<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnexoRequest;
use App\Models\Chamado;
use App\Models\ChamadoAnexo;
use App\Services\AnexoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ChamadoAnexoController extends Controller
{
    public function store(AnexoRequest $request, Chamado $chamado, AnexoService $anexoService)
    {
        DB::transaction(fn () => $anexoService->salvar($chamado, $request->file('anexos')));

        return redirect()
            ->route('chamados.show', $chamado)
            ->with('success', 'Anexo(s) enviado(s).');
    }

    /**
     * Download seguro: o arquivo fica no disco privado e só sai depois da ChamadoPolicy.
     */
    public function show(Chamado $chamado, ChamadoAnexo $anexo)
    {
        Gate::authorize('view', $chamado);

        $disco = Storage::disk(ChamadoAnexo::DISCO);
        abort_unless($disco->exists($anexo->caminho), 404);

        return $disco->download($anexo->caminho, $anexo->nome_arquivo);
    }

    public function destroy(Chamado $chamado, ChamadoAnexo $anexo, AnexoService $anexoService)
    {
        $anexoService->excluir($anexo);

        return redirect()
            ->route('chamados.show', $chamado)
            ->with('success', "Anexo {$anexo->nome_arquivo} excluído.");
    }
}
