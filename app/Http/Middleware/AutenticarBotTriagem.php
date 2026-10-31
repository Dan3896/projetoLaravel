<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticação entre serviços: o bot Node envia o token de BOT_TRIAGEM_TOKEN no header Authorization.
 */
class AutenticarBotTriagem
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('services.bot_triagem.token');

        // Sem token configurado a API fica fechada, para não aceitar um token vazio.
        if ($token === '') {
            return response()->json(['mensagem' => 'API do bot não configurada.'], 503);
        }

        if (! hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['mensagem' => 'Token inválido.'], 401);
        }

        return $next($request);
    }
}
