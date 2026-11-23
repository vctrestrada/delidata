<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ticket imprimible (formato de impresora térmica de 80 mm).
 */
class TicketController extends Controller
{
    public function __invoke(Request $request, Venta $venta): View
    {
        $usuario = $request->user();

        $puedeVerlo = match ($usuario->rolPrincipal()) {
            Rol::Dueno => $venta->negocio_id === $usuario->negocio_id,
            Rol::Encargado => $venta->restaurante_id === $usuario->restaurante_id,
            Rol::Vendedor => $venta->vendedor_id === $usuario->id,
            default => false,
        };

        abort_unless($puedeVerlo, 404);

        return view('tickets.venta', [
            'venta' => $venta->load(['detalles', 'restaurante', 'negocio', 'vendedor']),
        ]);
    }
}
