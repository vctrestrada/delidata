<?php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class VentaCancelada implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public Venta $venta) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('negocio.'.$this->venta->negocio_id),
            new PrivateChannel('restaurante.'.$this->venta->restaurante_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'venta.cancelada';
    }

    public function broadcastWith(): array
    {
        $this->venta->loadMissing('restaurante:id,nombre');

        return [
            'folio' => $this->venta->folioFormateado(),
            'total' => (float) $this->venta->total,
            'restaurante' => $this->venta->restaurante->nombre,
        ];
    }
}
