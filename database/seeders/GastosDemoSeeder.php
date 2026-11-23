<?php

namespace Database\Seeders;

use App\Enums\CategoriaGasto;
use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Models\Gasto;
use App\Models\Negocio;
use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Gastos de los mismos ~45 días que VentasDemoSeeder.
 * Centro queda con buena utilidad; Plaza Neza con margen apretado
 * (renta de plaza más cara), para que el dashboard muestre contrastes.
 */
class GastosDemoSeeder extends Seeder
{
    private const DIAS = 45;

    public function run(): void
    {
        mt_srand(4242);

        $negocio = Negocio::where('nombre', 'La Esquina Taquería')->firstOrFail();

        foreach ($negocio->restaurantes as $restaurante) {
            $esCentro = str_contains($restaurante->nombre, 'Centro');

            $this->generar($restaurante, [
                'renta' => $esCentro ? 18000 : 26000,
                'quincena' => $esCentro ? 9500 : 8000,
                'insumos' => $esCentro ? 2300 : 1750, // cada 2 días aprox.
                'luz' => $esCentro ? 2100 : 1900,
                'agua' => $esCentro ? 650 : 480,
                'gas' => $esCentro ? 1400 : 1100,
            ]);
        }
    }

    private function generar(Restaurante $restaurante, array $montos): void
    {
        $encargado = User::where('restaurante_id', $restaurante->id)->role(Rol::Encargado->value)->value('id');
        $dueno = $restaurante->negocio->dueno_id;

        for ($d = self::DIAS; $d >= 0; $d--) {
            $dia = today()->subDays($d);

            // Insumos: casi a diario, alternando proveedores.
            if ($d % 2 === 0) {
                $this->gasto($restaurante, $encargado, $dia, CategoriaGasto::Insumos, 'Carne para pastor y suadero', $this->variar($montos['insumos']), MetodoPago::Efectivo, 'Carnicería Don Pepe');
            }
            if ($d % 3 === 0) {
                $this->gasto($restaurante, $encargado, $dia, CategoriaGasto::Insumos, 'Tortillas', $this->variar(420), MetodoPago::Efectivo, 'Tortillería La Guadalupana');
            }
            if ($d % 4 === 1) {
                $this->gasto($restaurante, $encargado, $dia, CategoriaGasto::Insumos, 'Verdura, salsas y desechables', $this->variar(780), MetodoPago::Efectivo, 'Central de Abasto');
            }
            if ($d % 7 === 2) {
                $this->gasto($restaurante, $encargado, $dia, CategoriaGasto::Insumos, 'Refrescos y agua embotellada', $this->variar(1350), MetodoPago::Transferencia, 'Distribuidora del Valle');
            }

            // Renta: el día 1 de cada mes.
            if ($dia->day === 1) {
                $this->gasto($restaurante, $dueno, $dia, CategoriaGasto::Renta, 'Renta del local '.$dia->translatedFormat('F'), $montos['renta'], MetodoPago::Transferencia, 'Arrendador');
            }

            // Nómina: días 15 y último del mes.
            if ($dia->day === 15 || $dia->isSameDay($dia->copy()->endOfMonth())) {
                $this->gasto($restaurante, $dueno, $dia, CategoriaGasto::Nomina, 'Nómina quincenal', $montos['quincena'], MetodoPago::Transferencia, null);
            }

            // Servicios: luz, agua y gas en distintos días del mes.
            if ($dia->day === 8) {
                $this->gasto($restaurante, $encargado, $dia, CategoriaGasto::Servicios, 'Recibo de luz', $this->variar($montos['luz']), MetodoPago::Tarjeta, 'CFE');
            }
            if ($dia->day === 12) {
                $this->gasto($restaurante, $encargado, $dia, CategoriaGasto::Servicios, 'Recibo de agua', $this->variar($montos['agua']), MetodoPago::Efectivo, 'OPDM');
            }
            if ($dia->day % 10 === 5) {
                $this->gasto($restaurante, $encargado, $dia, CategoriaGasto::Servicios, 'Cilindro de gas', $this->variar($montos['gas']), MetodoPago::Efectivo, 'Gas Express');
            }
            if ($dia->day === 20) {
                $this->gasto($restaurante, $dueno, $dia, CategoriaGasto::Servicios, 'Internet y teléfono', 499, MetodoPago::Tarjeta, 'Telmex');
            }

            // Mantenimiento y otros, de vez en cuando.
            if (mt_rand(1, 100) <= 6) {
                $this->gasto($restaurante, $encargado, $dia, CategoriaGasto::Mantenimiento, collect(['Afilado de cuchillos', 'Reparación del trompo', 'Productos de limpieza', 'Fumigación'])->random(), $this->variar(650), MetodoPago::Efectivo, null);
            }
            if (mt_rand(1, 100) <= 3) {
                $this->gasto($restaurante, $encargado, $dia, CategoriaGasto::Otros, 'Bolsas y servilletas impresas', $this->variar(380), MetodoPago::Efectivo, null);
            }
        }
    }

    private function variar(float $monto): float
    {
        return round($monto * mt_rand(85, 115) / 100, 2);
    }

    private function gasto(Restaurante $restaurante, ?int $usuario, Carbon $dia, CategoriaGasto $categoria, string $concepto, float $monto, MetodoPago $metodo, ?string $proveedor): void
    {
        Gasto::create([
            'negocio_id' => $restaurante->negocio_id,
            'restaurante_id' => $restaurante->id,
            'registrado_por' => $usuario,
            'fecha' => $dia,
            'categoria' => $categoria,
            'concepto' => $concepto,
            'monto' => $monto,
            'metodo_pago' => $metodo,
            'proveedor' => $proveedor,
        ]);
    }
}
