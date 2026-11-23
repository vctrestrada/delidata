<?php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Se emite cuando un vendedor cobra. Lo escuchan el dueño (canal del negocio)
 * y el encargado (canal del restaurante) para actualizar sus pantallas.
 */
class VentaRegistrada implements ShouldBroadcastNow
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
        return 'venta.registrada';
    }

    /** Solo lo necesario para la notificación; nunca el detalle completo. */
    public function broadcastWith(): array
    {
        $this->venta->loadMissing(['restaurante:id,nombre', 'vendedor:id,name']);

        return [
            'folio' => $this->venta->folioFormateado(),
            'total' => (float) $this->venta->total,
            'metodo' => $this->venta->metodo_pago->etiqueta(),
            'restaurante' => $this->venta->restaurante->nombre,
            'vendedor' => $this->venta->vendedor?->name,
        ];
    }
}
