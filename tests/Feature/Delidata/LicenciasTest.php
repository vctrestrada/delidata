<?php

namespace Tests\Feature\Delidata;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use App\Enums\Rol;
use App\Models\Licencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenario;
use Tests\TestCase;

class LicenciasTest extends TestCase
{
    use CreaEscenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    private function licencia(string $inicio, string $fin, EstadoLicencia $estado = EstadoLicencia::Activa): Licencia
    {
        return new Licencia([
            'plan' => PlanLicencia::Basico,
            'max_restaurantes' => 1,
            'precio' => 499,
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'estado' => $estado,
        ]);
    }

    public function test_calcula_el_estado_segun_las_fechas(): void
    {
        $this->assertSame(EstadoLicencia::Activa, $this->licencia(today()->subDay(), today()->addMonth())->estadoActual());
        $this->assertSame(EstadoLicencia::PorVencer, $this->licencia(today()->subMonth(), today()->addDays(5))->estadoActual());
        $this->assertSame(EstadoLicencia::Vencida, $this->licencia(today()->subMonths(2), today()->subDay())->estadoActual());
        $this->assertSame(EstadoLicencia::Programada, $this->licencia(today()->addDay(), today()->addMonth())->estadoActual());
        $this->assertSame(EstadoLicencia::Suspendida, $this->licencia(today()->subDay(), today()->addMonth(), EstadoLicencia::Suspendida)->estadoActual());
    }

    public function test_el_personal_de_un_negocio_vencido_no_puede_entrar(): void
    {
        $negocio = $this->negocio(licencia: ['fecha_inicio' => today()->subMonths(3), 'fecha_fin' => today()->subDay()]);
        $restaurante = $this->restaurante($negocio);
        $vendedor = $this->personal(Rol::Vendedor, $restaurante);

        $this->actingAs($negocio->dueno)->get(route('negocio.inicio'))->assertRedirect(route('licencia.inactiva'));
        $this->actingAs($vendedor)->get(route('ventas.inicio'))->assertRedirect(route('licencia.inactiva'));
        $this->actingAs($vendedor)->get(route('licencia.inactiva'))->assertOk()->assertSee('no está vigente');
    }

    public function test_un_vendedor_de_un_restaurante_desactivado_no_puede_vender(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio, ['activo' => false]);
        $vendedor = $this->personal(Rol::Vendedor, $restaurante);

        $this->actingAs($vendedor)->get(route('ventas.inicio'))->assertRedirect(route('licencia.inactiva'));
    }

    public function test_un_usuario_desactivado_pierde_la_sesion(): void
    {
        $negocio = $this->negocio();
        $vendedor = $this->personal(Rol::Vendedor, $this->restaurante($negocio), ['activo' => false]);

        $this->actingAs($vendedor)->get(route('ventas.inicio'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
