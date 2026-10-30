<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForcePasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WaterDeliveryController;
use Illuminate\Support\Facades\Route;

// Redireciona a raiz para o login (se não autenticado, o middleware redireciona automaticamente)
Route::get('/', function () {
    return redirect()->route('entregas.nova');
});

// Troca de senha: acessível mesmo quando a troca é obrigatória (não usa o middleware de bloqueio)
Route::middleware('auth')->group(function () {
    Route::get('/alterar-senha', [ForcePasswordController::class, 'edit'])->name('password.force');
    Route::post('/alterar-senha', [ForcePasswordController::class, 'update'])->name('password.force.update');
});

// Rotas do SCEA protegidas por autenticação obrigatória e política de senha
Route::middleware(['auth', 'require.password.change'])->prefix('entregas')->name('entregas.')->group(function () {
    // Nova Entrega
    Route::get('/nova', [WaterDeliveryController::class, 'index'])->name('nova');
    Route::post('/', [WaterDeliveryController::class, 'store'])->name('store');

    // Validação e Baixa
    Route::get('/baixa', [WaterDeliveryController::class, 'baixaPage'])->name('baixa');
    Route::get('/api/search/{orderNumber}', [WaterDeliveryController::class, 'searchOrder'])->name('api.search');
    Route::post('/redeem/{orderNumber}', [WaterDeliveryController::class, 'redeem'])->name('redeem');

    // Relatórios e Histórico
    Route::get('/relatorios', [WaterDeliveryController::class, 'relatoriosPage'])->name('relatorios');
});

Route::middleware(['auth', 'require.password.change'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Gestão de usuários — exclusiva do perfil administrador
Route::middleware(['auth', 'require.password.change', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('users.index');
        Route::post('/usuarios', [UserController::class, 'store'])->name('users.store');
        Route::patch('/usuarios/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::patch('/usuarios/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::delete('/usuarios/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

require __DIR__.'/auth.php';
