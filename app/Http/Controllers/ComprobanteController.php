<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entrega el comprobante de un gasto solo a quien tiene permiso de verlo.
 * Los archivos viven en el disco privado (storage/app/private), sin URL pública.
 */
class ComprobanteController extends Controller
{
    public function __invoke(Request $request, Gasto $gasto): StreamedResponse
    {
        $usuario = $request->user();

        $puedeVerlo = match ($usuario->rolPrincipal()) {
            Rol::Dueno => $gasto->negocio_id === $usuario->negocio_id,
            Rol::Encargado => $gasto->restaurante_id === $usuario->restaurante_id,
            default => false,
        };

        $disco = Storage::disk(Gasto::DISCO_COMPROBANTES);

        abort_unless($puedeVerlo && $gasto->comprobante && $disco->exists($gasto->comprobante), 404);

        return $disco->response(
            $gasto->comprobante,
            'comprobante-gasto-'.$gasto->id.'.'.pathinfo($gasto->comprobante, PATHINFO_EXTENSION),
            ['X-Content-Type-Options' => 'nosniff'],
            'inline',
        );
    }
}
