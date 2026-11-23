<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenciaInactivaController;
use App\Http\Controllers\RecorridoController;
use App\Livewire\Admin;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirige al panel según el rol del usuario.
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Pantalla que ve el personal cuando su restaurante no puede operar.
    Route::get('licencia-inactiva', LicenciaInactivaController::class)->name('licencia.inactiva');

    // Marca un recorrido guiado como visto.
    Route::post('recorridos/{clave}', RecorridoController::class)->name('recorridos.visto');

    // Super administrador: restaurantes y licencias de la plataforma.
    Route::middleware('role:super-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/', Admin\Inicio::class)->name('inicio');

        Route::livewire('restaurantes', Admin\Restaurantes\Index::class)->name('restaurantes.index');
        Route::livewire('restaurantes/crear', Admin\Restaurantes\Formulario::class)->name('restaurantes.crear');
        Route::livewire('restaurantes/{restaurante}', Admin\Restaurantes\Detalle::class)->name('restaurantes.detalle');
        Route::livewire('restaurantes/{restaurante}/editar', Admin\Restaurantes\Formulario::class)->name('restaurantes.editar');
    });

    // A partir de aquí todo requiere que el restaurante tenga licencia vigente.
    Route::middleware('licencia')->group(function () {
        // Dueño: su restaurante, sucursales y dashboard del negocio.
        Route::middleware('role:dueno')->prefix('negocio')->name('negocio.')->group(function () {
            Route::view('/', 'panel.dueno')->name('inicio');
        });

        // Encargado: operación de una sucursal.
        Route::middleware('role:encargado')->prefix('sucursal')->name('sucursal.')->group(function () {
            Route::view('/', 'panel.encargado')->name('inicio');
        });

        // Vendedor: punto de venta.
        Route::middleware('role:vendedor')->prefix('ventas')->name('ventas.')->group(function () {
            Route::view('/', 'panel.vendedor')->name('inicio');
        });
    });
});

require __DIR__.'/settings.php';
