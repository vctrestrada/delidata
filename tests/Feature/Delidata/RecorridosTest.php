<?php

namespace Tests\Feature\Delidata;

use App\Enums\Rol;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaEscenario;
use Tests\TestCase;

class RecorridosTest extends TestCase
{
    use CreaEscenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    public function test_una_guia_vista_ya_no_se_abre_sola(): void
    {
        $dueno = $this->negocio()->dueno;

        $this->actingAs($dueno)->postJson(route('recorridos.visto', 'negocio.inicio'))->assertOk();

        $this->assertTrue($dueno->fresh()->haVistoRecorrido('negocio.inicio'));
        $this->assertFalse($dueno->fresh()->haVistoRecorrido('negocio.productos'));
    }

    public function test_desactivar_y_reiniciar_todas_las_guias(): void
    {
        $vendedor = $this->personal(Rol::Vendedor, $this->restaurante($this->negocio()));

        $this->actingAs($vendedor)->postJson(route('recorridos.desactivar'))->assertOk();
        $this->assertTrue($vendedor->fresh()->haVistoRecorrido('cualquier.pantalla'));

        $this->actingAs($vendedor)->post(route('recorridos.reiniciar'))->assertRedirect();
        $this->assertFalse($vendedor->fresh()->haVistoRecorrido('cualquier.pantalla'));
    }

    public function test_rechaza_claves_de_guia_invalidas(): void
    {
        $this->actingAs($this->superAdmin())->postJson('/recorridos/'.rawurlencode('<script>'))->assertStatus(422);
    }
}
