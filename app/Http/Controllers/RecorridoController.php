<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registra que el usuario ya vio el recorrido guiado de una pantalla.
 */
class RecorridoController extends Controller
{
    public function __invoke(Request $request, string $clave): JsonResponse
    {
        abort_unless(preg_match('/^[a-z0-9.\-]{1,80}$/', $clave) === 1, 422);

        $request->user()->marcarRecorridoVisto($clave);

        return response()->json(['ok' => true]);
    }
}
