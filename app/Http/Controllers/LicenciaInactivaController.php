<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla que ve el personal cuando no puede operar
 * (suscripción vencida, negocio desactivado o restaurante desactivado).
 */
class LicenciaInactivaController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $usuario = $request->user();
        $rol = $usuario->rolPrincipal();

        // El super admin no depende de ninguna suscripción.
        if ($rol === Rol::SuperAdmin) {
            return redirect()->route('dashboard');
        }

        $negocio = $usuario->negocio()->with('licencias')->first();
        $restaurante = $usuario->restaurante;

        $motivo = match (true) {
            $negocio === null => 'sin-negocio',
            ! $negocio->activo => 'negocio-inactivo',
            ! $negocio->estadoLicencia()->permiteAcceso() => 'suscripcion',
            $rol?->trabajaEnRestaurante() && $restaurante === null => 'sin-restaurante',
            $rol?->trabajaEnRestaurante() && ! $restaurante->activo => 'restaurante-inactivo',
            default => null,
        };

        if ($motivo === null) {
            return redirect()->route('dashboard');
        }

        return view('panel.licencia-inactiva', [
            'motivo' => $motivo,
            'negocio' => $negocio,
            'restaurante' => $restaurante,
            'estado' => $negocio?->estadoLicencia(),
            'esDueno' => $rol === Rol::Dueno,
        ]);
    }
}
