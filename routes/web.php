<?php

use App\Http\Controllers\CompradorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Módulos de las siguientes fases: por ahora muestran "en construcción".
    Route::view('/remisiones/nueva', 'proximamente', ['modulo' => 'Nueva remisión', 'fase' => 4])->name('remisiones.create');
    Route::view('/remisiones', 'proximamente', ['modulo' => 'Remisiones', 'fase' => 5])->name('remisiones.index');
    Route::get('/compradores', [CompradorController::class, 'index'])->name('compradores.index');
    Route::post('/compradores', [CompradorController::class, 'store'])->name('compradores.store');
    Route::get('/materiales', [MaterialController::class, 'index'])->name('materiales.index');

    Route::middleware('can:admin')->group(function () {
        Route::patch('/compradores/{comprador}', [CompradorController::class, 'update'])->name('compradores.update');

        Route::post('/materiales', [MaterialController::class, 'store'])->name('materiales.store');
        Route::patch('/materiales/{material}', [MaterialController::class, 'update'])->name('materiales.update');
        Route::post('/materiales/{material}/stock', [MaterialController::class, 'ajustarStock'])->name('materiales.stock');

        Route::view('/cartera', 'proximamente', ['modulo' => 'Cartera', 'fase' => 5])->name('cartera.index');
        Route::view('/equipo', 'proximamente', ['modulo' => 'Equipo y vendedores', 'fase' => 6])->name('equipo.index');
        Route::view('/reportes', 'proximamente', ['modulo' => 'Reportes', 'fase' => 7])->name('reportes.index');
    });
});

require __DIR__.'/auth.php';
