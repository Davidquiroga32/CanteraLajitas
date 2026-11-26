<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Módulos de las siguientes fases: por ahora muestran "en construcción".
    Route::view('/remisiones/nueva', 'proximamente', ['modulo' => 'Nueva remisión', 'fase' => 4])->name('remisiones.create');
    Route::view('/remisiones', 'proximamente', ['modulo' => 'Remisiones', 'fase' => 5])->name('remisiones.index');
    Route::view('/compradores', 'proximamente', ['modulo' => 'Compradores', 'fase' => 3])->name('compradores.index');
    Route::view('/materiales', 'proximamente', ['modulo' => 'Materiales', 'fase' => 3])->name('materiales.index');

    Route::middleware('can:admin')->group(function () {
        Route::view('/cartera', 'proximamente', ['modulo' => 'Cartera', 'fase' => 5])->name('cartera.index');
        Route::view('/equipo', 'proximamente', ['modulo' => 'Equipo y vendedores', 'fase' => 6])->name('equipo.index');
        Route::view('/reportes', 'proximamente', ['modulo' => 'Reportes', 'fase' => 7])->name('reportes.index');
    });
});

require __DIR__.'/auth.php';
