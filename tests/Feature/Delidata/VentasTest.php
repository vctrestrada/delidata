<?php

namespace Tests\Feature\Delidata;

use App\Actions\Ventas\RegistrarVenta;
use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Livewire\Ventas\Historial;
use App\Livewire\Ventas\PuntoDeVenta;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\CreaEscenario;
use Tests\TestCase;

class VentasTest extends TestCase
{
    use CreaEscenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    public function test_cobra_con_el_precio_del_restaurante_y_calcula_el_cambio(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $taco = $this->producto($negocio, [$restaurante->id => ['precio' => 22]], ['precio' => 18]);
        $agua = $this->producto($negocio, [$restaurante->id => []], ['precio' => 30]);
        $vendedor = $this->personal(Rol::Vendedor, $restaurante);

        $venta = (new RegistrarVenta)($vendedor, [$taco->id => 3, $agua->id => 1], MetodoPago::Efectivo, 100);

        $this->assertSame('96.00', $venta->total);
        $this->assertSame('4.00', $venta->cambio);
        $this->assertSame(1, $venta->folio);
        $this->assertCount(2, $venta->detalles);
        $this->assertSame('22.00', $venta->detalles->firstWhere('producto_id', $taco->id)->precio_unitario);
    }

    public function test_el_folio_es_consecutivo_por_restaurante(): void
    {
        $negocio = $this->negocio();
        $a = $this->restaurante($negocio);
        $b = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$a->id => [], $b->id => []]);
        $vendedorA = $this->personal(Rol::Vendedor, $a);
        $vendedorB = $this->personal(Rol::Vendedor, $b);
        $registrar = new RegistrarVenta;

        $registrar($vendedorA, [$producto->id => 1], MetodoPago::Tarjeta);
        $segunda = $registrar($vendedorA, [$producto->id => 1], MetodoPago::Tarjeta);
        $otraSucursal = $registrar($vendedorB, [$producto->id => 1], MetodoPago::Tarjeta);

        $this->assertSame(2, $segunda->folio);
        $this->assertSame(1, $otraSucursal->folio);
    }

    public function test_no_permite_vender_un_producto_agotado(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$restaurante->id => ['disponible' => false]]);

        $this->expectException(ValidationException::class);

        (new RegistrarVenta)($this->personal(Rol::Vendedor, $restaurante), [$producto->id => 1], MetodoPago::Tarjeta);
    }

    public function test_no_permite_vender_productos_de_otro_restaurante(): void
    {
        $negocio = $this->negocio();
        $mio = $this->restaurante($negocio);
        $otro = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$otro->id => []]);

        $this->expectException(ValidationException::class);

        (new RegistrarVenta)($this->personal(Rol::Vendedor, $mio), [$producto->id => 1], MetodoPago::Tarjeta);
    }

    public function test_el_efectivo_debe_alcanzar(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$restaurante->id => []], ['precio' => 50]);

        $this->expectException(ValidationException::class);

        (new RegistrarVenta)($this->personal(Rol::Vendedor, $restaurante), [$producto->id => 1], MetodoPago::Efectivo, 20);
    }

    public function test_el_punto_de_venta_cobra_desde_la_pantalla(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$restaurante->id => []], ['precio' => 25]);
        $this->actingAs($this->personal(Rol::Vendedor, $restaurante));

        Livewire::test(PuntoDeVenta::class)
            ->call('agregar', $producto->id)
            ->call('agregar', $producto->id)
            ->set('metodo', MetodoPago::Efectivo->value)
            ->set('recibido', '100')
            ->call('cobrar')
            ->assertHasNoErrors()
            ->assertSet('carrito', [])
            ->assertSee('Cambio a entregar');

        $this->assertSame('50.00', Venta::first()->total);
    }

    public function test_el_encargado_cancela_una_venta_con_motivo(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$restaurante->id => []]);
        $venta = (new RegistrarVenta)($this->personal(Rol::Vendedor, $restaurante), [$producto->id => 1], MetodoPago::Tarjeta);
        $encargado = $this->personal(Rol::Encargado, $restaurante);
        $this->actingAs($encargado);

        Livewire::test(Historial::class)
            ->call('iniciarCancelacion', $venta->id)
            ->set('motivo', 'x')
            ->call('confirmarCancelacion')
            ->assertHasErrors(['motivo'])
            ->set('motivo', 'El cliente se arrepintió')
            ->call('confirmarCancelacion')
            ->assertHasNoErrors();

        $venta->refresh();
        $this->assertSame(EstadoVenta::Cancelada, $venta->estado);
        $this->assertSame($encargado->id, $venta->cancelada_por);
    }

    public function test_el_ticket_solo_lo_ve_quien_tiene_permiso(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$restaurante->id => []]);
        $vendedor = $this->personal(Rol::Vendedor, $restaurante);
        $venta = (new RegistrarVenta)($vendedor, [$producto->id => 1], MetodoPago::Tarjeta);

        $otroVendedor = $this->personal(Rol::Vendedor, $restaurante);
        $duenoAjeno = $this->negocio()->dueno;

        $this->actingAs($vendedor)->get(route('tickets.ver', $venta))->assertOk()->assertSee($venta->folioFormateado());
        $this->actingAs($negocio->dueno)->get(route('tickets.ver', $venta))->assertOk();
        $this->actingAs($otroVendedor)->get(route('tickets.ver', $venta))->assertNotFound();
        $this->actingAs($duenoAjeno)->get(route('tickets.ver', $venta))->assertNotFound();
    }
}
