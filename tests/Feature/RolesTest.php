<?php

namespace Tests\Feature;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use App\Enums\Rol;
use App\Models\Negocio;
use App\Models\Restaurante;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);
    }

    /** @return array<string, array{Rol, string}> */
    public static function rolesYRutas(): array
    {
        return [
            'super admin' => [Rol::SuperAdmin, '/admin'],
            'dueño' => [Rol::Dueno, '/negocio'],
            'encargado' => [Rol::Encargado, '/mi-restaurante'],
            'vendedor' => [Rol::Vendedor, '/ventas'],
        ];
    }

    /** Usuario con rol; si no es super admin, se liga a un negocio con suscripción vigente (y a un restaurante si aplica). */
    private function usuarioCon(Rol $rol): User
    {
        $usuario = User::factory()->create()->assignRole($rol->value);

        if ($rol !== Rol::SuperAdmin) {
            $negocio = Negocio::create(['nombre' => 'Negocio de prueba']);
            $negocio->licencias()->create([
                'plan' => PlanLicencia::Basico,
                'max_restaurantes' => 1,
                'precio' => 499,
                'fecha_inicio' => today()->subDay(),
                'fecha_fin' => today()->addMonth(),
                'estado' => EstadoLicencia::Activa,
            ]);
            $usuario->negocio()->associate($negocio);

            if ($rol->trabajaEnRestaurante()) {
                $usuario->restaurante()->associate(Restaurante::create(['negocio_id' => $negocio->id, 'nombre' => 'Centro']));
            }

            $usuario->save();
        }

        return $usuario;
    }

    #[DataProvider('rolesYRutas')]
    public function test_cada_rol_es_enviado_a_su_panel(Rol $rol, string $ruta): void
    {
        $usuario = $this->usuarioCon($rol);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertRedirect($ruta);

        $this->actingAs($usuario)->get($ruta)->assertOk();
    }

    public function test_un_vendedor_no_puede_entrar_al_panel_del_dueno(): void
    {
        $vendedor = $this->usuarioCon(Rol::Vendedor);

        $this->actingAs($vendedor)->get('/negocio')->assertForbidden();
    }

    public function test_un_usuario_sin_rol_ve_el_aviso_de_acceso(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tu cuenta aún no tiene un rol');
    }

    public function test_el_registro_publico_esta_desactivado(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
