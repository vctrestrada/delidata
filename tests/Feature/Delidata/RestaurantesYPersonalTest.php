<?php

namespace Tests\Feature\Delidata;

use App\Enums\PlanLicencia;
use App\Enums\Rol;
use App\Livewire\Negocio\Personal\Formulario as PersonalFormulario;
use App\Livewire\Negocio\Restaurantes\Formulario as RestauranteFormulario;
use App\Livewire\Negocio\Restaurantes\Index as RestaurantesIndex;
use App\Models\Restaurante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaEscenario;
use Tests\TestCase;

class RestaurantesYPersonalTest extends TestCase
{
    use CreaEscenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    public function test_el_dueno_registra_un_restaurante(): void
    {
        $negocio = $this->negocio();
        $this->actingAs($negocio->dueno);

        Livewire::test(RestauranteFormulario::class)
            ->set('nombre', 'Sucursal Centro')
            ->set('abre', '08:00')
            ->set('cierra', '22:00')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('restaurantes', ['negocio_id' => $negocio->id, 'nombre' => 'Sucursal Centro']);
    }

    public function test_no_se_puede_pasar_el_limite_del_plan(): void
    {
        $negocio = $this->negocio(PlanLicencia::Basico);
        $this->restaurante($negocio);
        $this->actingAs($negocio->dueno);

        // Con el límite alcanzado, el formulario ni siquiera se muestra: regresa al listado.
        Livewire::test(RestauranteFormulario::class)
            ->assertRedirect(route('negocio.restaurantes.index'));

        $this->assertSame(1, Restaurante::where('negocio_id', $negocio->id)->count());
    }

    public function test_un_dueno_no_puede_editar_restaurantes_de_otro_negocio(): void
    {
        $mio = $this->negocio();
        $ajeno = $this->restaurante($this->negocio());

        $this->actingAs($mio->dueno)
            ->get(route('negocio.restaurantes.editar', $ajeno))
            ->assertNotFound();
    }

    public function test_no_se_elimina_un_restaurante_con_personal(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $this->personal(Rol::Vendedor, $restaurante);
        $this->actingAs($negocio->dueno);

        Livewire::test(RestaurantesIndex::class)->call('eliminar', $restaurante->id);

        $this->assertModelExists($restaurante);
    }

    public function test_el_dueno_crea_un_encargado_sin_verificar_correo(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $this->actingAs($negocio->dueno);

        Livewire::test(PersonalFormulario::class)
            ->set('nombre', 'Laura Encargada')
            ->set('correo', 'laura@prueba.test')
            ->set('password', 'secreto123')
            ->set('rol', Rol::Encargado->value)
            ->set('restauranteId', (string) $restaurante->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $laura = User::where('email', 'laura@prueba.test')->firstOrFail();
        $this->assertTrue($laura->hasRole(Rol::Encargado->value));
        $this->assertSame($restaurante->id, $laura->restaurante_id);
        $this->assertNotNull($laura->email_verified_at);
    }

    public function test_el_encargado_solo_crea_vendedores_en_su_restaurante(): void
    {
        $negocio = $this->negocio();
        $suyo = $this->restaurante($negocio);
        $otro = $this->restaurante($negocio);
        $encargado = $this->personal(Rol::Encargado, $suyo);
        $this->actingAs($encargado);

        // Intenta crear un encargado en otro restaurante: se fuerza vendedor en el suyo.
        Livewire::test(PersonalFormulario::class)
            ->set('nombre', 'Pedro')
            ->set('correo', 'pedro@prueba.test')
            ->set('password', 'secreto123')
            ->set('rol', Rol::Encargado->value)
            ->set('restauranteId', (string) $otro->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $pedro = User::where('email', 'pedro@prueba.test')->firstOrFail();
        $this->assertTrue($pedro->hasRole(Rol::Vendedor->value));
        $this->assertFalse($pedro->hasRole(Rol::Encargado->value));
        $this->assertSame($suyo->id, $pedro->restaurante_id);
    }

    public function test_el_encargado_no_ve_vendedores_de_otro_restaurante(): void
    {
        $negocio = $this->negocio();
        $encargado = $this->personal(Rol::Encargado, $this->restaurante($negocio));
        $ajeno = $this->personal(Rol::Vendedor, $this->restaurante($negocio));

        $this->actingAs($encargado)
            ->get(route('encargado.personal.editar', $ajeno))
            ->assertNotFound();
    }
}
