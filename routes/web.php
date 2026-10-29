<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WaterDeliveryController;
use Illuminate\Support\Facades\Route;

// Redireciona a raiz para a tela principal do SCEA
Route::get('/', function () {
    return redirect()->route('entregas.nova');
});

// Rotas do SCEA (Controle de Entrega de Água - Almoxarifado PCPA)
Route::prefix('entregas')->name('entregas.')->group(function () {
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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
