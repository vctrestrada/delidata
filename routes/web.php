<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Redirige al panel según el rol del usuario.
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Super administrador: licencias y restaurantes de la plataforma.
    Route::middleware('role:super-admin')->prefix('admin')->name('admin.')->group(function () {
        Route::view('/', 'panel.super-admin')->name('inicio');
    });

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

require __DIR__.'/settings.php';
