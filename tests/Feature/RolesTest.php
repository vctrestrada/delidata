<?php

namespace Tests\Feature;

use App\Enums\Rol;
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
            'encargado' => [Rol::Encargado, '/sucursal'],
            'vendedor' => [Rol::Vendedor, '/ventas'],
        ];
    }

    #[DataProvider('rolesYRutas')]
    public function test_cada_rol_es_enviado_a_su_panel(Rol $rol, string $ruta): void
    {
        $usuario = User::factory()->create()->assignRole($rol->value);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertRedirect($ruta);

        $this->actingAs($usuario)->get($ruta)->assertOk();
    }

    public function test_un_vendedor_no_puede_entrar_al_panel_del_dueno(): void
    {
        $vendedor = User::factory()->create()->assignRole(Rol::Vendedor->value);

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
