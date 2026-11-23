<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenciaInactivaController;
use App\Http\Controllers\RecorridoController;
use App\Livewire\Admin;
use App\Livewire\Encargado;
use App\Livewire\Negocio;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirige al panel según el rol del usuario.
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Pantalla que ve el personal cuando no puede operar.
    Route::get('acceso-no-disponible', LicenciaInactivaController::class)->name('licencia.inactiva');

    // Marca un recorrido guiado como visto.
    Route::post('recorridos/{clave}', RecorridoController::class)->name('recorridos.visto');

    /*
    |--------------------------------------------------------------------------
    | Super admin: solo administra las suscripciones de los negocios
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:super-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('/', Admin\Inicio::class)->name('inicio');

        Route::livewire('suscripciones', Admin\Suscripciones\Index::class)->name('suscripciones.index');
        Route::livewire('suscripciones/nueva', Admin\Suscripciones\Formulario::class)->name('suscripciones.crear');
        Route::livewire('suscripciones/{negocio}', Admin\Suscripciones\Detalle::class)->name('suscripciones.detalle');
        Route::livewire('suscripciones/{negocio}/editar', Admin\Suscripciones\Formulario::class)->name('suscripciones.editar');
    });

    /*
    |--------------------------------------------------------------------------
    | Personal del negocio: requiere suscripción vigente
    |--------------------------------------------------------------------------
    */
    Route::middleware('licencia')->group(function () {
        // Dueño: administrador de su negocio (restaurantes y personal).
        Route::middleware('role:dueno')->prefix('negocio')->name('negocio.')->group(function () {
            Route::livewire('/', Negocio\Inicio::class)->name('inicio');

            Route::livewire('restaurantes', Negocio\Restaurantes\Index::class)->name('restaurantes.index');
            Route::livewire('restaurantes/nuevo', Negocio\Restaurantes\Formulario::class)->name('restaurantes.crear');
            Route::livewire('restaurantes/{restaurante}/editar', Negocio\Restaurantes\Formulario::class)->name('restaurantes.editar');

            Route::livewire('productos', Negocio\Productos\Index::class)->name('productos.index');
            Route::livewire('productos/nuevo', Negocio\Productos\Formulario::class)->name('productos.crear');
            Route::livewire('productos/categorias', Negocio\Productos\Categorias::class)->name('productos.categorias');
            Route::livewire('productos/{producto}/editar', Negocio\Productos\Formulario::class)->name('productos.editar');

            Route::livewire('personal', Negocio\Personal\Index::class)->name('personal.index');
            Route::livewire('personal/nuevo', Negocio\Personal\Formulario::class)->name('personal.crear');
            Route::livewire('personal/{usuario}/editar', Negocio\Personal\Formulario::class)->name('personal.editar');
        });

        // Encargado: su restaurante y sus vendedores.
        Route::middleware('role:encargado')->prefix('mi-restaurante')->name('encargado.')->group(function () {
            Route::livewire('/', Encargado\Inicio::class)->name('inicio');
            Route::livewire('menu', Encargado\Menu::class)->name('menu');

            Route::livewire('vendedores', Negocio\Personal\Index::class)->name('personal.index');
            Route::livewire('vendedores/nuevo', Negocio\Personal\Formulario::class)->name('personal.crear');
            Route::livewire('vendedores/{usuario}/editar', Negocio\Personal\Formulario::class)->name('personal.editar');
        });

        // Vendedor: punto de venta.
        Route::middleware('role:vendedor')->prefix('ventas')->name('ventas.')->group(function () {
            Route::view('/', 'panel.vendedor')->name('inicio');
        });
    });
});

require __DIR__.'/settings.php';
