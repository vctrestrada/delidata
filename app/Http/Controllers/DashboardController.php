<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Punto de entrada después del login: envía a cada usuario
 * al panel que corresponde a su rol.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $rol = $request->user()->rolPrincipal();

        if ($rol === null) {
            return view('panel.sin-rol');
        }

        return redirect()->route($rol->rutaInicio());
    }
}
