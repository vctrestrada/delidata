<?php

namespace Database\Seeders;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use App\Enums\Rol;
use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Restaurantes de ejemplo con licencias en distintos estados,
 * para ver el panel del super admin con datos reales.
 *
 * El dueño, encargado y vendedor demo quedan en "La Esquina Taquería".
 */
class RestaurantesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $hoy = today();

        $restaurantes = [
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

        foreach ($restaurantes as $datos) {
            $dueno = is_string($datos['dueno'])
                ? User::where('email', $datos['dueno'])->firstOrFail()
                : (User::where('email', $datos['dueno'][1])->first()
                    ?? User::factory()->create(['name' => $datos['dueno'][0], 'email' => $datos['dueno'][1], 'password' => 'password']));

            $dueno->syncRoles([Rol::Dueno->value]);

            $restaurante = Restaurante::updateOrCreate(
                ['nombre' => $datos['nombre']],
                [
                    'rfc' => $datos['rfc'], 'telefono' => $datos['telefono'], 'correo' => $datos['correo'],
                    'direccion' => $datos['direccion'], 'dueno_id' => $dueno->id, 'activo' => true,
                ],
            );

            $dueno->restaurante()->associate($restaurante)->save();

            if ($restaurante->licencias()->doesntExist()) {
                $restaurante->licencias()->create([
                    'plan' => $datos['plan'],
                    'max_sucursales' => $datos['plan']->maxSucursales(),
                    'precio' => $datos['plan']->precioMensual() * $datos['meses'],
                    'fecha_inicio' => $datos['inicio'],
                    'fecha_fin' => $datos['inicio']->addMonthsNoOverflow($datos['meses'])->subDay(),
                    'estado' => $datos['estado'],
                    'notas' => 'Licencia de ejemplo',
                ]);
            }
        }

        // El personal demo trabaja en La Esquina Taquería.
        $laEsquina = Restaurante::where('nombre', 'La Esquina Taquería')->first();

        User::whereIn('email', ['encargado@delidata.test', 'vendedor@delidata.test'])
            ->update(['restaurante_id' => $laEsquina->id]);
    }
}
