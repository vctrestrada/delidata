<?php

namespace Tests\Feature\Delidata;

use App\Enums\Rol;
use App\Livewire\Encargado\Menu;
use App\Livewire\Negocio\Productos\Formulario;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaEscenario;
use Tests\TestCase;

class ProductosTest extends TestCase
{
    use CreaEscenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    public function test_el_dueno_crea_un_producto_con_precio_especial_por_restaurante(): void
    {
        $negocio = $this->negocio();
        $centro = $this->restaurante($negocio);
        $plaza = $this->restaurante($negocio);
        $this->actingAs($negocio->dueno);

        Livewire::test(Formulario::class)
            ->set('nombre', 'Taco al pastor')
            ->set('precio', '18')
            ->set('costo', '7.50')
            ->set("enRestaurantes.{$centro->id}.vende", true)
            ->set("enRestaurantes.{$plaza->id}.vende", true)
            ->set("enRestaurantes.{$plaza->id}.precio", '22')
            ->call('guardar')
            ->assertHasNoErrors();

        $producto = Producto::where('nombre', 'Taco al pastor')->firstOrFail();

        $this->assertSame(58, $producto->margen());
        $this->assertSame(18.0, $producto->precioEn($centro));
        $this->assertSame(22.0, $producto->precioEn($plaza));
    }

    public function test_editar_el_producto_respeta_lo_que_el_encargado_marco_como_agotado(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$restaurante->id => ['disponible' => false]]);
        $this->actingAs($negocio->dueno);

        Livewire::test(Formulario::class, ['producto' => $producto])
            ->set('precio', '25')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertFalse((bool) $producto->restaurantes()->first()->pivot->disponible);
    }

    public function test_el_encargado_marca_un_producto_como_agotado(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $producto = $this->producto($negocio, [$restaurante->id => []]);
        $this->actingAs($this->personal(Rol::Encargado, $restaurante));

        Livewire::test(Menu::class)->call('alternarDisponible', $producto->id);

        $this->assertFalse((bool) $restaurante->productos()->first()->pivot->disponible);
    }

    public function test_el_vendedor_no_puede_entrar_al_catalogo(): void
    {
        $negocio = $this->negocio();
        $vendedor = $this->personal(Rol::Vendedor, $this->restaurante($negocio));

        $this->actingAs($vendedor)->get(route('negocio.productos.index'))->assertForbidden();
    }
}
