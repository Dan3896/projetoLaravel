<?php

use App\Http\Controllers\Api\BotChamadoController;
use App\Http\Middleware\AutenticarBotTriagem;
use Illuminate\Support\Facades\Route;

// API usada pelo serviço Node do bot de triagem (prefixo /api).
Route::middleware([AutenticarBotTriagem::class, 'throttle:60,1'])->prefix('bot')->name('api.bot.')->group(function () {
    Route::get('/tipos-chamado', [BotChamadoController::class, 'tipos'])->name('tipos');
    Route::post('/chamados', [BotChamadoController::class, 'store'])->name('chamados.store');
});
