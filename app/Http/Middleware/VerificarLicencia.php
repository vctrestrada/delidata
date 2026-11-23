<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impide que el personal de un negocio use el sistema cuando:
 *  - el negocio está desactivado o su suscripción no está vigente, o
 *  - (encargados y vendedores) su restaurante no existe o está desactivado.
 */
class VerificarLicencia
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        $negocio = $usuario?->negocio()->with('licencias')->first();

        if ($negocio === null || ! $negocio->puedeOperar()) {
            return redirect()->route('licencia.inactiva');
        }

        if ($usuario->rolPrincipal()?->trabajaEnRestaurante() && ! $usuario->restaurante?->activo) {
            return redirect()->route('licencia.inactiva');
        }

        return $next($request);
    }
}
