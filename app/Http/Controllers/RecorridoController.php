<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Preferencias de los recorridos guiados del usuario.
 */
class RecorridoController extends Controller
{
    /** Marca como visto el recorrido de una pantalla. */
    public function __invoke(Request $request, string $clave): JsonResponse
    {
        abort_unless(preg_match('/^[a-z0-9.\-]{1,80}$/', $clave) === 1, 422);

        $request->user()->marcarRecorridoVisto($clave);

        return response()->json(['ok' => true]);
    }

    /** No volver a iniciar guías automáticamente. */
    public function desactivar(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->desactivarRecorridos();

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('status', 'Las guías ya no se abrirán solas.');
    }

    /** Volver a mostrar todas las guías desde el principio. */
    public function reiniciar(Request $request): RedirectResponse
    {
        $request->user()->reiniciarRecorridos();

        return back();
    }
}
