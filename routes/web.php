<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChamadoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChamadoAnexoController;
use App\Http\Controllers\ChamadoCancelamentoController;
use App\Http\Controllers\ChamadoEtapaController;
use App\Http\Controllers\ChamadoStatusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoricoInternoController;
use App\Http\Controllers\OrcamentoController;
use App\Http\Controllers\TriagemController;
use App\Http\Controllers\Admin\EmpresaController;
use App\Http\Controllers\Admin\EtapaController;
use App\Http\Controllers\Admin\PrazoSlaController;
use App\Http\Controllers\Admin\TipoChamadoController;
use App\Http\Controllers\Admin\UserController;

Route::get('/', function () {
    return auth()->user()?->hasRole('admin')
        ? redirect()->route('dashboard')
        : redirect('/chamados');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'ativo'])->group(function () {
    Route::resource('chamados', ChamadoController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/chamados/{chamado}/cancelar', [ChamadoCancelamentoController::class, 'store'])->name('chamados.cancelar');

    // Anexos e orçamento: o {anexo}/{orcamento} precisa pertencer ao {chamado} da URL.
    Route::scopeBindings()->group(function () {
        Route::post('/chamados/{chamado}/anexos', [ChamadoAnexoController::class, 'store'])->name('chamados.anexos.store');
        Route::get('/chamados/{chamado}/anexos/{anexo}', [ChamadoAnexoController::class, 'show'])->name('chamados.anexos.show');
        Route::post('/chamados/{chamado}/orcamentos/{orcamento}/responder', [OrcamentoController::class, 'responder'])->name('chamados.orcamentos.responder');
        Route::get('/chamados/{chamado}/orcamentos/{orcamento}/pdf', [OrcamentoController::class, 'pdf'])->name('chamados.orcamentos.pdf');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/chamados/{chamado}/edit', [ChamadoController::class, 'edit'])->name('chamados.edit');
        Route::put('/chamados/{chamado}', [ChamadoController::class, 'update'])->name('chamados.update');
        Route::get('/chamados/{chamado}/triagem', [TriagemController::class, 'create'])->name('chamados.triagem.create');
        Route::post('/chamados/{chamado}/triagem', [TriagemController::class, 'store'])->name('chamados.triagem.store');
        Route::patch('/chamados/{chamado}/status', [ChamadoStatusController::class, 'update'])->name('chamados.status.update');
        Route::patch('/chamados/{chamado}/etapa', [ChamadoEtapaController::class, 'update'])->name('chamados.etapa.update');
        Route::post('/chamados/{chamado}/historicos-internos', [HistoricoInternoController::class, 'store'])->name('chamados.historicos-internos.store');
        Route::post('/chamados/{chamado}/orcamentos', [OrcamentoController::class, 'store'])->name('chamados.orcamentos.store');

        Route::scopeBindings()->group(function () {
            Route::delete('/chamados/{chamado}/anexos/{anexo}', [ChamadoAnexoController::class, 'destroy'])->name('chamados.anexos.destroy');
            Route::get('/chamados/{chamado}/orcamentos/{orcamento}/edit', [OrcamentoController::class, 'edit'])->name('chamados.orcamentos.edit');
            Route::put('/chamados/{chamado}/orcamentos/{orcamento}', [OrcamentoController::class, 'update'])->name('chamados.orcamentos.update');
        });
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('usuarios', UserController::class)->except(['show', 'destroy']);
        Route::patch('/usuarios/{usuario}/ativo', [UserController::class, 'alternarAtivo'])->name('usuarios.ativo');
        Route::resource('empresas', EmpresaController::class)->except(['show', 'destroy']);
        Route::resource('etapas', EtapaController::class)->except(['show']);
        Route::resource('tipos-chamado', TipoChamadoController::class)->except(['show', 'destroy'])->parameters(['tipos-chamado' => 'tipo']);
        Route::get('/prazos-sla', [PrazoSlaController::class, 'edit'])->name('prazos-sla.edit');
        Route::put('/prazos-sla', [PrazoSlaController::class, 'update'])->name('prazos-sla.update');
    });
});
