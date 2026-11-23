<?php

namespace Database\Seeders;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Genera ~45 días de ventas para "La Esquina Taquería" con un patrón realista:
 * más ventas en fin de semana y a la hora de la comida y de la cena.
 * Usa una semilla fija para que los datos sean siempre los mismos.
 */
class VentasDemoSeeder extends Seeder
{
    private const DIAS = 45;

    public function run(): void
    {
        mt_srand(2026);

        $negocio = Negocio::where('nombre', 'La Esquina Taquería')->firstOrFail();

        foreach ($negocio->restaurantes()->with('productos')->get() as $restaurante) {
            $this->generar($restaurante);
        }
    }

    private function generar(Restaurante $restaurante): void
    {
        $productos = $restaurante->productos->where('activo', true)->values();
        $vendedores = User::where('restaurante_id', $restaurante->id)->role(Rol::Vendedor->value)->pluck('id')->all();
        $encargado = User::where('restaurante_id', $restaurante->id)->role(Rol::Encargado->value)->value('id');

        if ($productos->isEmpty() || $vendedores === []) {
            return;
        }

        // El restaurante más grande vende más.
        $base = str_contains($restaurante->nombre, 'Centro') ? 26 : 16;
        $abre = (int) substr((string) $restaurante->abre ?: '09', 0, 2);
        $cierra = (int) substr((string) $restaurante->cierra ?: '22', 0, 2);
        $ahora = now();

        $folio = (int) DB::table('ventas')->where('restaurante_id', $restaurante->id)->max('folio');

        for ($d = self::DIAS; $d >= 0; $d--) {
            $dia = today()->subDays($d);
            $esFinDeSemana = $dia->isWeekend();
            $cantidadVentas = (int) round($base * ($esFinDeSemana ? 1.45 : 1) * (mt_rand(80, 120) / 100));

            // Horas de la venta, con picos a la comida (14-16 h) y la cena (20-22 h).
            $horas = [];
            for ($i = 0; $i < $cantidadVentas; $i++) {
                $hora = $this->horaPonderada($abre, $cierra);
                $momento = Carbon::parse($dia->toDateString())->setTime($hora, mt_rand(0, 59), mt_rand(0, 59));

                if ($momento->lessThan($ahora)) {
                    $horas[] = $momento;
                }
            }

            sort($horas);

            foreach ($horas as $momento) {
                $folio++;
                $this->crearVenta($restaurante, $productos, $vendedores, $encargado, $folio, $momento);
            }
        }
    }

    private function horaPonderada(int $abre, int $cierra): int
    {
        $pesos = [];
        for ($h = $abre; $h < $cierra; $h++) {
            $pesos[$h] = match (true) {
                $h >= 14 && $h <= 15 => 5,
                $h >= 20 && $h <= 21 => 4,
                $h >= 12 && $h <= 16 => 3,
                $h >= 19 => 3,
                default => 1,
            };
        }

        $tirada = mt_rand(1, array_sum($pesos));
        foreach ($pesos as $hora => $peso) {
            $tirada -= $peso;
            if ($tirada <= 0) {
                return $hora;
            }
        }

        return $abre;
    }

    private function crearVenta(Restaurante $restaurante, $productos, array $vendedores, ?int $encargado, int $folio, Carbon $momento): void
    {
        $renglones = [];
        $subtotal = 0;
        $elegidos = $productos->shuffle()->take(mt_rand(1, 4));

        foreach ($elegidos as $producto) {
            $cantidad = str_contains($producto->nombre, 'Taco') ? mt_rand(2, 6) : mt_rand(1, 2);
            $precio = (float) ($producto->pivot->precio ?? $producto->precio);
            $importe = round($precio * $cantidad, 2);
            $subtotal += $importe;

            $renglones[] = [
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio,
                'costo_unitario' => $producto->costo,
                'importe' => $importe,
                'created_at' => $momento,
                'updated_at' => $momento,
            ];
        }

        $tirada = mt_rand(1, 100);
        $metodo = $tirada <= 62 ? MetodoPago::Efectivo : ($tirada <= 90 ? MetodoPago::Tarjeta : MetodoPago::Transferencia);
        $descuento = mt_rand(1, 100) <= 5 ? round($subtotal * 0.1, 2) : 0;
        $total = round($subtotal - $descuento, 2);
        $recibido = $metodo === MetodoPago::Efectivo ? $this->billete($total) : null;
        $cancelada = mt_rand(1, 100) <= 2;

        $ventaId = DB::table('ventas')->insertGetId([
            'negocio_id' => $restaurante->negocio_id,
            'restaurante_id' => $restaurante->id,
            'vendedor_id' => $vendedores[array_rand($vendedores)],
            'folio' => $folio,
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
            'metodo_pago' => $metodo->value,
            'recibido' => $recibido,
            'cambio' => $recibido !== null ? round($recibido - $total, 2) : null,
            'estado' => $cancelada ? EstadoVenta::Cancelada->value : EstadoVenta::Completada->value,
            'notas' => mt_rand(1, 100) <= 15 ? 'Para llevar' : null,
            'cancelada_por' => $cancelada ? $encargado : null,
            'cancelada_at' => $cancelada ? $momento->copy()->addMinutes(5) : null,
            'motivo_cancelacion' => $cancelada ? 'Se registró dos veces' : null,
            'created_at' => $momento,
            'updated_at' => $momento,
        ]);

        DB::table('venta_detalles')->insert(array_map(fn ($r) => $r + ['venta_id' => $ventaId], $renglones));
    }

    /** Billete con el que paga el cliente: exacto o el siguiente billete común. */
    private function billete(float $total): float
    {
        if (mt_rand(1, 100) <= 25) {
            return $total;
        }

        foreach ([50, 100, 200, 500, 1000] as $billete) {
            if ($billete >= $total) {
                return (float) $billete;
            }
        }

        return ceil($total / 100) * 100;
    }
}
