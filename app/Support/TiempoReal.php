<?php

namespace App\Support;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Throwable;

/**
 * Emite un evento en tiempo real sin poner en riesgo la operación.
 *
 * Si el servidor de Reverb está apagado, la venta o el gasto se guardan
 * igual: el error solo se registra en el log y la pantalla se actualizará
 * la próxima vez que el usuario la recargue.
 */
class TiempoReal
{
    public static function emitir(ShouldBroadcast $evento): void
    {
        try {
            broadcast($evento);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
