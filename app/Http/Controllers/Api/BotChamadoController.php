<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BotChamadoRequest;
use App\Models\TipoChamado;
use App\Services\ChamadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BotChamadoController extends Controller
{
    /**
     * Tipos de chamado que o bot pode usar na classificação.
     */
    public function tipos(): JsonResponse
    {
        return response()->json(TipoChamado::orderBy('descricao')->get(['id', 'descricao']));
    }

    /**
     * Cria o chamado já classificado pelo bot e grava a conversa no histórico interno.
     */
    public function store(BotChamadoRequest $request, ChamadoService $chamadoService): JsonResponse
    {
        $usuario = $request->usuario();
        $dados = $request->validated();

        $chamado = DB::transaction(function () use ($chamadoService, $usuario, $dados) {
            $chamado = $chamadoService->abrir([
                'cliente_id' => $usuario->cliente_id,
                'tipo_chamado_id' => $dados['tipo_chamado_id'],
                'descricao' => $dados['descricao'],
                'criticidade_cliente' => $dados['criticidade_cliente'] ?? null,
            ]);

            if (! empty($dados['conversa'])) {
                $transcricao = collect($dados['conversa'])
                    ->map(fn ($mensagem) => "[{$mensagem['autor']}] {$mensagem['texto']}")
                    ->implode("\n");

                $chamadoService->registrarHistoricoInterno($chamado, null, "Conversa com o bot de triagem ({$usuario->name}):\n{$transcricao}");
            }

            return $chamado;
        });

        return response()->json([
            'id' => $chamado->id,
            'url' => route('chamados.show', $chamado),
        ], 201);
    }
}
