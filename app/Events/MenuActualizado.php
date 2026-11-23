<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Se emite cuando cambia lo que se puede vender en uno o varios restaurantes
 * (producto agotado, desactivado, precio nuevo…). El punto de venta lo escucha
 * para que los vendedores nunca ofrezcan algo que ya no hay.
 */
class MenuActualizado implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    /** @param  list<int>  $restaurantes */
    public function __construct(public array $restaurantes, public string $mensaje = '') {}

    public function broadcastOn(): array
    {
        return array_map(fn (int $id) => new PrivateChannel('restaurante.'.$id), array_values(array_unique($this->restaurantes)));
    }

    public function broadcastAs(): string
    {
        return 'menu.actualizado';
    }

    public function broadcastWith(): array
    {
        return ['mensaje' => $this->mensaje];
    }
}
