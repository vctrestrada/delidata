<?php

use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenciaInactivaController;
use App\Http\Controllers\RecorridoController;
use App\Http\Controllers\TicketController;
use App\Livewire\Admin;
use App\Livewire\Dashboard;
use App\Livewire\Encargado;
use App\Livewire\Gastos;
use App\Livewire\Negocio;
use App\Livewire\Ventas;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirige al panel según el rol del usuario.
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Pantalla que ve el personal cuando no puede operar.
    Route::get('acceso-no-disponible', LicenciaInactivaController::class)->name('licencia.inactiva');

    // Recorridos guiados: desactivar, reiniciar o marcar uno como visto.
    Route::post('recorridos/desactivar', [RecorridoController::class, 'desactivar'])->name('recorridos.desactivar');
    Route::post('recorridos/reiniciar', [RecorridoController::class, 'reiniciar'])->name('recorridos.reiniciar');
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
            Route::livewire('dashboard', Dashboard\Panel::class)->name('dashboard');

            Route::livewire('restaurantes', Negocio\Restaurantes\Index::class)->name('restaurantes.index');
            Route::livewire('restaurantes/nuevo', Negocio\Restaurantes\Formulario::class)->name('restaurantes.crear');
            Route::livewire('restaurantes/{restaurante}/editar', Negocio\Restaurantes\Formulario::class)->name('restaurantes.editar');

            Route::livewire('productos', Negocio\Productos\Index::class)->name('productos.index');
            Route::livewire('productos/nuevo', Negocio\Productos\Formulario::class)->name('productos.crear');
            Route::livewire('productos/categorias', Negocio\Productos\Categorias::class)->name('productos.categorias');
            Route::livewire('productos/{producto}/editar', Negocio\Productos\Formulario::class)->name('productos.editar');

            Route::livewire('ventas', Ventas\Historial::class)->name('ventas');
            Route::livewire('gastos', Gastos\Index::class)->name('gastos.index');
            Route::livewire('gastos/nuevo', Gastos\Formulario::class)->name('gastos.crear');
            Route::livewire('gastos/{gasto}/editar', Gastos\Formulario::class)->name('gastos.editar');

            Route::livewire('personal', Negocio\Personal\Index::class)->name('personal.index');
            Route::livewire('personal/nuevo', Negocio\Personal\Formulario::class)->name('personal.crear');
            Route::livewire('personal/{usuario}/editar', Negocio\Personal\Formulario::class)->name('personal.editar');
        });

        // Encargado: su restaurante y sus vendedores.
        Route::middleware('role:encargado')->prefix('mi-restaurante')->name('encargado.')->group(function () {
            Route::livewire('/', Encargado\Inicio::class)->name('inicio');
            Route::livewire('dashboard', Dashboard\Panel::class)->name('dashboard');
            Route::livewire('menu', Encargado\Menu::class)->name('menu');
            Route::livewire('ventas', Ventas\Historial::class)->name('ventas');
            Route::livewire('gastos', Gastos\Index::class)->name('gastos.index');
            Route::livewire('gastos/nuevo', Gastos\Formulario::class)->name('gastos.crear');
            Route::livewire('gastos/{gasto}/editar', Gastos\Formulario::class)->name('gastos.editar');

            Route::livewire('vendedores', Negocio\Personal\Index::class)->name('personal.index');
            Route::livewire('vendedores/nuevo', Negocio\Personal\Formulario::class)->name('personal.crear');
            Route::livewire('vendedores/{usuario}/editar', Negocio\Personal\Formulario::class)->name('personal.editar');
        });

        // Vendedor: punto de venta.
        Route::middleware('role:vendedor')->prefix('ventas')->name('ventas.')->group(function () {
            Route::livewire('/', Ventas\PuntoDeVenta::class)->name('inicio');
            Route::livewire('historial', Ventas\MisVentas::class)->name('historial');
        });

        // Comprobante privado de un gasto (dueño o encargado del restaurante).
        Route::get('gastos/{gasto}/comprobante', ComprobanteController::class)
            ->middleware('role:dueno|encargado')
            ->name('gastos.comprobante');

        // Ticket imprimible (dueño, encargado o el vendedor que hizo la venta).
        Route::get('tickets/{venta}', TicketController::class)
            ->middleware('role:dueno|encargado|vendedor')
            ->name('tickets.ver');
    });
});

require __DIR__.'/settings.php';
