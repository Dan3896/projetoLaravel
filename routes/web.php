<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChamadoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChamadoStatusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TriagemController;
use App\Http\Controllers\Admin\UserController;

Route::get('/', function () {
    return auth()->user()?->hasRole('admin')
        ? redirect()->route('dashboard')
        : redirect('/chamados');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::resource('chamados', ChamadoController::class);

    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/chamados/{chamado}/triagem', [TriagemController::class, 'create'])->name('chamados.triagem.create');
        Route::post('/chamados/{chamado}/triagem', [TriagemController::class, 'store'])->name('chamados.triagem.store');
        Route::patch('/chamados/{chamado}/status', [ChamadoStatusController::class, 'update'])->name('chamados.status.update');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('usuarios', UserController::class)->only(['index', 'create', 'store']);
    });
});