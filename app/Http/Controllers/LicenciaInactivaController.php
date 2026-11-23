<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantalla que ve el personal cuando su restaurante no puede operar.
 */
class LicenciaInactivaController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $usuario = $request->user();

        // El super admin no depende de ninguna licencia.
        if ($usuario->hasRole(Rol::SuperAdmin->value)) {
            return redirect()->route('dashboard');
        }

        $restaurante = $usuario->restaurante()->with('licencias')->first();

        if ($restaurante?->puedeOperar()) {
            return redirect()->route('dashboard');
        }

        return view('panel.licencia-inactiva', [
            'restaurante' => $restaurante,
            'estado' => $restaurante?->estadoLicencia(),
            'esDueno' => $usuario->hasRole(Rol::Dueno->value),
        ]);
    }
}
