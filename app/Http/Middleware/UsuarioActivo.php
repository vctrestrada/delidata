<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de los usuarios que el dueño o el encargado desactivaron.
 */
class UsuarioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->activo === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'Tu cuenta está desactivada. Habla con el administrador de tu restaurante.');
        }

        return $next($request);
    }
}
