<?php

use App\Http\Controllers\CarteraController;
use App\Http\Controllers\CompradorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RemisionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Módulos de las siguientes fases: por ahora muestran "en construcción".
    Route::get('/remisiones/nueva', [RemisionController::class, 'create'])->name('remisiones.create');
    Route::post('/remisiones', [RemisionController::class, 'store'])->name('remisiones.store');
    Route::get('/remisiones', [RemisionController::class, 'index'])->name('remisiones.index');
    Route::get('/remisiones/exportar/excel', [RemisionController::class, 'exportarExcel'])->name('remisiones.excel');
    Route::get('/remisiones/exportar/pdf', [RemisionController::class, 'exportarPdf'])->name('remisiones.listado-pdf');
    Route::get('/remisiones/{remision}', [RemisionController::class, 'show'])->whereNumber('remision')->name('remisiones.show');
    Route::get('/remisiones/{remision}/imprimir', [RemisionController::class, 'imprimir'])->whereNumber('remision')->name('remisiones.imprimir');
    Route::get('/remisiones/{remision}/pdf', [RemisionController::class, 'pdf'])->whereNumber('remision')->name('remisiones.pdf');
    Route::get('/compradores/buscar', [RemisionController::class, 'buscarCompradores'])->name('compradores.buscar');
    Route::get('/compradores', [CompradorController::class, 'index'])->name('compradores.index');
    Route::post('/compradores', [CompradorController::class, 'store'])->name('compradores.store');
    Route::get('/materiales', [MaterialController::class, 'index'])->name('materiales.index');

    Route::middleware('can:admin')->group(function () {
        Route::patch('/compradores/{comprador}', [CompradorController::class, 'update'])->name('compradores.update');

        Route::post('/materiales', [MaterialController::class, 'store'])->name('materiales.store');
        Route::patch('/materiales/{material}', [MaterialController::class, 'update'])->name('materiales.update');
        Route::post('/materiales/{material}/stock', [MaterialController::class, 'ajustarStock'])->name('materiales.stock');

        Route::post('/remisiones/{remision}/anular', [RemisionController::class, 'anular'])->whereNumber('remision')->name('remisiones.anular');

        Route::get('/cartera', [CarteraController::class, 'index'])->name('cartera.index');
        Route::get('/cartera/exportar/excel', [CarteraController::class, 'exportarExcel'])->name('cartera.excel');
        Route::get('/cartera/exportar/pdf', [CarteraController::class, 'exportarPdf'])->name('cartera.pdf');
        Route::get('/cartera/{comprador}', [CarteraController::class, 'show'])->whereNumber('comprador')->name('cartera.show');
        Route::post('/cartera/{comprador}/abono', [CarteraController::class, 'abono'])->whereNumber('comprador')->name('cartera.abono');
        Route::post('/cartera/{comprador}/anticipo', [CarteraController::class, 'anticipo'])->whereNumber('comprador')->name('cartera.anticipo');
        Route::get('/cartera/{comprador}/estado-cuenta/excel', [CarteraController::class, 'estadoCuentaExcel'])->whereNumber('comprador')->name('cartera.estado-excel');
        Route::get('/cartera/{comprador}/estado-cuenta/pdf', [CarteraController::class, 'estadoCuentaPdf'])->whereNumber('comprador')->name('cartera.estado-pdf');
        Route::get('/equipo', [EquipoController::class, 'index'])->name('equipo.index');
        Route::post('/equipo', [EquipoController::class, 'store'])->name('equipo.store');
        Route::patch('/equipo/{usuario}', [EquipoController::class, 'update'])->whereNumber('usuario')->name('equipo.update');
        Route::put('/equipo/{usuario}/contrasena', [EquipoController::class, 'asignarContrasena'])->whereNumber('usuario')->name('equipo.contrasena');
        Route::view('/reportes', 'proximamente', ['modulo' => 'Reportes', 'fase' => 7])->name('reportes.index');
    });
});

require __DIR__.'/auth.php';
