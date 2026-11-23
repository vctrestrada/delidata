<?php

namespace App\Actions\Ventas;

use App\Enums\EstadoVenta;
use App\Events\VentaCancelada;
use App\Models\User;
use App\Models\Venta;
use App\Support\TiempoReal;
use Illuminate\Validation\ValidationException;

/**
 * Cancela una venta. Solo el encargado o el dueño pueden hacerlo, y debe quedar el motivo.
 * La venta no se borra: queda marcada como cancelada y deja de contar en los totales.
 */
class CancelarVenta
{
    public function __invoke(Venta $venta, User $usuario, string $motivo): Venta
    {
        if ($venta->estaCancelada()) {
            throw ValidationException::withMessages(['motivo' => 'Esta venta ya estaba cancelada.']);
        }

        $venta->update([
            'estado' => EstadoVenta::Cancelada,
            'cancelada_por' => $usuario->id,
            'cancelada_at' => now(),
            'motivo_cancelacion' => trim($motivo),
        ]);

        TiempoReal::emitir(new VentaCancelada($venta));

        return $venta;
    }
}
