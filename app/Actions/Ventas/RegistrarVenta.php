<?php

namespace App\Actions\Ventas;

use App\Enums\EstadoVenta;
use App\Events\VentaRegistrada;
use App\Enums\MetodoPago;
use App\Models\Producto;
use App\Models\Restaurante;
use App\Models\User;
use App\Models\Venta;
use App\Support\TiempoReal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registra una venta del punto de venta.
 *
 * Los precios NO vienen del navegador: se vuelven a leer de la base de datos
 * (precio especial del restaurante o precio base) y se verifica que cada
 * producto siga activo y disponible en el restaurante del vendedor.
 */
class RegistrarVenta
{
    /**
     * @param  array<int, int>  $carrito  [producto_id => cantidad]
     */
    public function __invoke(
        User $vendedor,
        array $carrito,
        MetodoPago $metodo,
        ?float $recibido = null,
        float $descuento = 0,
        ?string $notas = null,
    ): Venta {
        $carrito = array_filter($carrito, fn ($cantidad) => (int) $cantidad > 0);

        if ($carrito === []) {
            throw ValidationException::withMessages(['carrito' => 'Agrega al menos un producto.']);
        }

        $venta = DB::transaction(function () use ($vendedor, $carrito, $metodo, $recibido, $descuento, $notas) {
            // Se bloquea el restaurante para que dos ventas simultáneas no tomen el mismo folio.
            $restaurante = Restaurante::whereKey($vendedor->restaurante_id)->lockForUpdate()->firstOrFail();

            $productos = $restaurante->productos()
                ->whereIn('productos.id', array_keys($carrito))
                ->get()
                ->keyBy('id');

            $renglones = [];
            $subtotal = 0.0;

            foreach ($carrito as $productoId => $cantidad) {
                /** @var Producto|null $producto */
                $producto = $productos->get($productoId);

                if (! $producto || ! $producto->activo || ! $producto->pivot->disponible) {
                    $nombre = $producto?->nombre ?? 'Un producto';
                    throw ValidationException::withMessages(['carrito' => "{$nombre} ya no está disponible. Quítalo de la venta."]);
                }

                $precio = (float) ($producto->pivot->precio ?? $producto->precio);
                $importe = round($precio * (int) $cantidad, 2);
                $subtotal += $importe;

                $renglones[] = [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'cantidad' => (int) $cantidad,
                    'precio_unitario' => $precio,
                    'costo_unitario' => $producto->costo,
                    'importe' => $importe,
                ];
            }

            $descuento = round(max(0, min($descuento, $subtotal)), 2);
            $total = round($subtotal - $descuento, 2);

            if ($metodo === MetodoPago::Efectivo) {
                if ($recibido === null || $recibido < $total) {
                    throw ValidationException::withMessages(['recibido' => 'El efectivo recibido no alcanza para cubrir el total.']);
                }
            } else {
                $recibido = null;
            }

            $venta = Venta::create([
                'negocio_id' => $restaurante->negocio_id,
                'restaurante_id' => $restaurante->id,
                'vendedor_id' => $vendedor->id,
                'folio' => (int) Venta::where('restaurante_id', $restaurante->id)->max('folio') + 1,
                'subtotal' => round($subtotal, 2),
                'descuento' => $descuento,
                'total' => $total,
                'metodo_pago' => $metodo,
                'recibido' => $recibido,
                'cambio' => $recibido !== null ? round($recibido - $total, 2) : null,
                'estado' => EstadoVenta::Completada,
                'notas' => $notas ?: null,
            ]);

            $venta->detalles()->createMany($renglones);

            return $venta->load('detalles');
        });

        // Avisar en tiempo real al dueño y al encargado.
        TiempoReal::emitir(new VentaRegistrada($venta));

        return $venta;
    }
}
