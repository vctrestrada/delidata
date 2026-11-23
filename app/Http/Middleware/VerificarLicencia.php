<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impide que el personal de un restaurante use el sistema cuando
 * el restaurante está desactivado o su licencia no está vigente.
 */
class VerificarLicencia
{
    public function handle(Request $request, Closure $next): Response
    {
        $restaurante = $request->user()?->restaurante()->with('licencias')->first();

        if ($restaurante === null || ! $restaurante->puedeOperar()) {
            return redirect()->route('licencia.inactiva');
        }

        return $next($request);
    }
}
