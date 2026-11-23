<?php

namespace Tests\Concerns;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use App\Enums\Rol;
use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use App\Models\Restaurante;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;

/**
 * Arma escenarios de prueba: un negocio con suscripción, restaurantes,
 * personal y productos, sin depender de los datos de ejemplo.
 */
trait CreaEscenario
{
    protected function sembrarRoles(): void
    {
        $this->seed(RolesYPermisosSeeder::class);
    }

    protected function usuario(Rol $rol, array $atributos = []): User
    {
        return User::factory()->create($atributos)->assignRole($rol->value);
    }

    protected function superAdmin(): User
    {
        return $this->usuario(Rol::SuperAdmin);
    }

    /** Negocio con dueño y licencia vigente del plan indicado. */
    protected function negocio(PlanLicencia $plan = PlanLicencia::Profesional, array $licencia = []): Negocio
    {
        $dueno = $this->usuario(Rol::Dueno);
        $negocio = Negocio::create(['nombre' => 'Negocio '.fake()->unique()->company(), 'dueno_id' => $dueno->id]);
        $dueno->forceFill(['negocio_id' => $negocio->id])->save();

        $negocio->licencias()->create([
            'plan' => $plan,
            'max_restaurantes' => $plan->maxRestaurantes(),
            'precio' => $plan->precioMensual(),
            'fecha_inicio' => today()->subDays(10),
            'fecha_fin' => today()->addMonths(2),
            'estado' => EstadoLicencia::Activa,
            ...$licencia,
        ]);

        return $negocio->fresh(['dueno', 'licencias']);
    }

    protected function restaurante(Negocio $negocio, array $atributos = []): Restaurante
    {
        return Restaurante::create(['negocio_id' => $negocio->id, 'nombre' => 'Rest '.fake()->unique()->word(), ...$atributos]);
    }

    /** Encargado o vendedor de un restaurante. */
    protected function personal(Rol $rol, Restaurante $restaurante, array $atributos = []): User
    {
        $usuario = $this->usuario($rol, $atributos);
        $usuario->forceFill(['negocio_id' => $restaurante->negocio_id, 'restaurante_id' => $restaurante->id])->save();

        return $usuario;
    }

    /** Producto que se vende en los restaurantes indicados. [id => ['precio' => x, 'disponible' => bool]] */
    protected function producto(Negocio $negocio, array $restaurantes, array $atributos = []): Producto
    {
        $categoria = Categoria::firstOrCreate(['negocio_id' => $negocio->id, 'nombre' => 'General']);

        $producto = Producto::create([
            'negocio_id' => $negocio->id,
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto '.fake()->unique()->word(),
            'precio' => 20,
            'costo' => 8,
            ...$atributos,
        ]);

        $producto->restaurantes()->sync(collect($restaurantes)->mapWithKeys(fn ($config, $id) => [
            $id => ['precio' => $config['precio'] ?? null, 'disponible' => $config['disponible'] ?? true],
        ])->all());

        return $producto;
    }
}
