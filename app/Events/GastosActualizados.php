<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Se emite al registrar, editar o eliminar un gasto, para refrescar
 * el dashboard y la lista de gastos de quien los esté viendo.
 */
class GastosActualizados implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public int $negocioId, public int $restauranteId) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('negocio.'.$this->negocioId),
            new PrivateChannel('restaurante.'.$this->restauranteId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'gastos.actualizados';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
