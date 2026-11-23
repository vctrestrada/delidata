<?php

namespace Tests\Feature\Delidata;

use App\Actions\Ventas\RegistrarVenta;
use App\Enums\CategoriaGasto;
use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Livewire\Dashboard\Panel;
use App\Models\Gasto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Livewire\Livewire;
use Tests\Concerns\CreaEscenario;
use Tests\TestCase;

class DashboardYCanalesTest extends TestCase
{
    use CreaEscenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    public function test_el_dashboard_calcula_utilidad_y_proporcion_de_gastos(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$restaurante->id => []], ['precio' => 100]);
        (new RegistrarVenta)($this->personal(Rol::Vendedor, $restaurante), [$producto->id => 10], MetodoPago::Tarjeta);

        Gasto::create([
            'negocio_id' => $negocio->id, 'restaurante_id' => $restaurante->id, 'fecha' => today(),
            'categoria' => CategoriaGasto::Insumos, 'concepto' => 'Carne', 'monto' => 700, 'metodo_pago' => 'efectivo',
        ]);

        $this->actingAs($negocio->dueno);

        $indicadores = Livewire::test(Panel::class)->assertOk()->instance()->indicadores;

        $this->assertEqualsWithDelta(1000.0, $indicadores['ventas'], 0.01);
        $this->assertEqualsWithDelta(300.0, $indicadores['utilidad'], 0.01);
        $this->assertEqualsWithDelta(70.0, $indicadores['proporcion'], 0.01);
    }

    public function test_el_encargado_ve_el_dashboard_de_su_restaurante(): void
    {
        $negocio = $this->negocio();

        $this->actingAs($this->personal(Rol::Encargado, $this->restaurante($negocio)))
            ->get(route('encargado.dashboard'))
            ->assertOk()
            ->assertSee('Velocímetro de gastos');
    }

    public function test_los_canales_privados_solo_aceptan_a_quien_corresponde(): void
    {
        require base_path('routes/channels.php');
        $canales = Broadcast::driver()->getChannels();

        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $vendedor = $this->personal(Rol::Vendedor, $restaurante);
        $ajeno = $this->negocio();

        $canalNegocio = $canales['negocio.{negocioId}'];
        $canalRestaurante = $canales['restaurante.{restauranteId}'];

        $this->assertTrue($canalNegocio($negocio->dueno, $negocio->id));
        $this->assertFalse($canalNegocio($ajeno->dueno, $negocio->id));
        $this->assertFalse($canalNegocio($vendedor, $negocio->id));

        $this->assertTrue($canalRestaurante($vendedor, $restaurante->id));
        $this->assertTrue($canalRestaurante($negocio->dueno, $restaurante->id));
        $this->assertFalse($canalRestaurante($ajeno->dueno, $restaurante->id));

        $vendedor->forceFill(['activo' => false])->save();
        $this->assertFalse($canalRestaurante($vendedor->fresh(), $restaurante->id));
    }
}
