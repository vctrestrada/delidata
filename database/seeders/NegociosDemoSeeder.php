<?php

namespace Database\Seeders;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Negocios de ejemplo con suscripciones en distintos estados.
 *
 * "La Esquina Taquería" (del dueño demo) tiene 2 restaurantes,
 * con el encargado y el vendedor demo en "La Esquina — Centro".
 */
class NegociosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $hoy = today();

        $negocios = [
            [
                'nombre' => 'La Esquina Taquería', 'rfc' => 'ETA240115KX3', 'telefono' => '55 2301 8890',
                'correo' => 'contacto@laesquina.mx', 'direccion' => 'Av. Pantitlán 120, Nezahualcóyotl, Edo. Méx.',
                'dueno' => 'dueno@delidata.test', 'plan' => PlanLicencia::Profesional,
                'inicio' => $hoy->subMonths(3), 'meses' => 12, 'estado' => EstadoLicencia::Activa,
            ],
            [
                'nombre' => 'Café Aurora', 'rfc' => 'CAU230901MN7', 'telefono' => '55 4410 2215',
                'correo' => 'hola@cafeaurora.mx', 'direccion' => 'Calle 7 #45, Col. Esperanza, Nezahualcóyotl',
                'dueno' => ['Mariana López', 'mariana@cafeaurora.test'], 'plan' => PlanLicencia::Basico,
                'inicio' => $hoy->subMonths(1)->addDays(8), 'meses' => 1, 'estado' => EstadoLicencia::Activa,
            ],
            [
                'nombre' => 'Pizzería Don Leo', 'rfc' => null, 'telefono' => '55 7781 0034',
                'correo' => null, 'direccion' => 'Av. Chimalhuacán 890, Nezahualcóyotl',
                'dueno' => ['Leonardo Ríos', 'leo@donleo.test'], 'plan' => PlanLicencia::Basico,
                'inicio' => $hoy->subMonths(7), 'meses' => 6, 'estado' => EstadoLicencia::Activa,
            ],
            [
                'nombre' => 'Mariscos El Puerto', 'rfc' => 'MEP220610TT1', 'telefono' => '55 9012 3344',
                'correo' => 'admin@elpuerto.mx', 'direccion' => 'Blvd. Bordo de Xochiaca 300, Nezahualcóyotl',
                'dueno' => ['Ramón Herrera', 'ramon@elpuerto.test'], 'plan' => PlanLicencia::Empresarial,
                'inicio' => $hoy->subMonths(2), 'meses' => 12, 'estado' => EstadoLicencia::Suspendida,
            ],
        ];

        foreach ($negocios as $datos) {
            $dueno = is_string($datos['dueno'])
                ? User::where('email', $datos['dueno'])->firstOrFail()
                : (User::where('email', $datos['dueno'][1])->first()
                    ?? User::factory()->create(['name' => $datos['dueno'][0], 'email' => $datos['dueno'][1], 'password' => 'password']));

            $dueno->syncRoles([Rol::Dueno->value]);

            $negocio = Negocio::updateOrCreate(
                ['nombre' => $datos['nombre']],
                [
                    'rfc' => $datos['rfc'], 'telefono' => $datos['telefono'], 'correo' => $datos['correo'],
                    'direccion' => $datos['direccion'], 'dueno_id' => $dueno->id, 'activo' => true,
                ],
            );

            $dueno->negocio()->associate($negocio)->save();

            if ($negocio->licencias()->doesntExist()) {
                $negocio->licencias()->create([
                    'plan' => $datos['plan'],
                    'max_restaurantes' => $datos['plan']->maxRestaurantes(),
                    'precio' => $datos['plan']->precioMensual() * $datos['meses'],
                    'fecha_inicio' => $datos['inicio'],
                    'fecha_fin' => $datos['inicio']->addMonthsNoOverflow($datos['meses'])->subDay(),
                    'estado' => $datos['estado'],
                    'notas' => 'Licencia de ejemplo',
                ]);
            }
        }

        $this->restaurantesDeLaEsquina();
    }

    /** Restaurantes y personal del negocio demo. */
    private function restaurantesDeLaEsquina(): void
    {
        $negocio = Negocio::where('nombre', 'La Esquina Taquería')->firstOrFail();

        $centro = Restaurante::updateOrCreate(
            ['negocio_id' => $negocio->id, 'nombre' => 'La Esquina — Centro'],
            ['direccion' => 'Av. Pantitlán 120, Col. Centro', 'telefono' => '55 2301 8890', 'abre' => '08:00', 'cierra' => '23:00'],
        );

        $plaza = Restaurante::updateOrCreate(
            ['negocio_id' => $negocio->id, 'nombre' => 'La Esquina — Plaza Neza'],
            ['direccion' => 'Plaza Ciudad Jardín, local 14', 'telefono' => '55 2301 8891', 'abre' => '11:00', 'cierra' => '21:00'],
        );

        $personal = [
            ['encargado@delidata.test', 'Encargado Demo', Rol::Encargado, $centro],
            ['vendedor@delidata.test', 'Vendedor Demo', Rol::Vendedor, $centro],
            ['sofia@laesquina.test', 'Sofía Martínez', Rol::Vendedor, $centro],
            ['jorge@laesquina.test', 'Jorge Castillo', Rol::Encargado, $plaza],
            ['ana@laesquina.test', 'Ana Gutiérrez', Rol::Vendedor, $plaza],
        ];

        foreach ($personal as [$correo, $nombre, $rol, $restaurante]) {
            $usuario = User::where('email', $correo)->first()
                ?? User::factory()->create(['name' => $nombre, 'email' => $correo, 'password' => 'password']);

            $usuario->forceFill(['negocio_id' => $negocio->id, 'restaurante_id' => $restaurante->id])->save();
            $usuario->syncRoles([$rol->value]);
        }
    }
}
