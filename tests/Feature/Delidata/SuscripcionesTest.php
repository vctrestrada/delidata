<?php

namespace Tests\Feature\Delidata;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use App\Enums\Rol;
use App\Livewire\Admin\Suscripciones\Detalle;
use App\Livewire\Admin\Suscripciones\Formulario;
use App\Models\Licencia;
use App\Models\Negocio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaEscenario;
use Tests\TestCase;

class SuscripcionesTest extends TestCase
{
    use CreaEscenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
    }

    public function test_el_super_admin_ve_el_listado_de_suscripciones(): void
    {
        $this->negocio();

        $this->actingAs($this->superAdmin())
            ->get(route('admin.suscripciones.index'))
            ->assertOk()
            ->assertSee('Suscripciones');
    }

    public function test_un_dueno_no_puede_entrar_al_panel_del_super_admin(): void
    {
        $negocio = $this->negocio();

        $this->actingAs($negocio->dueno)->get(route('admin.suscripciones.index'))->assertForbidden();
    }

    public function test_al_registrar_una_suscripcion_se_crean_negocio_dueno_y_licencia(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(Formulario::class)
            ->set('nombre', 'Tacos El Güero')
            ->set('rfc', 'teg240101ab1')
            ->set('duenoNombre', 'Carlos Pérez')
            ->set('duenoCorreo', 'CARLOS@guero.test')
            ->set('duenoPassword', 'secreto123')
            ->set('plan', PlanLicencia::Basico->value)
            ->set('meses', 3)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect();

        $negocio = Negocio::where('nombre', 'Tacos El Güero')->firstOrFail();
        $dueno = User::where('email', 'carlos@guero.test')->firstOrFail();

        $this->assertSame('TEG240101AB1', $negocio->rfc);
        $this->assertSame($dueno->id, $negocio->dueno_id);
        $this->assertSame($negocio->id, $dueno->negocio_id);
        $this->assertTrue($dueno->hasRole(Rol::Dueno->value));
        $this->assertNotNull($dueno->email_verified_at);

        $licencia = $negocio->licencias()->first();
        $this->assertSame(PlanLicencia::Basico, $licencia->plan);
        $this->assertSame(1, $licencia->max_restaurantes);
        $this->assertTrue($licencia->fecha_fin->isSameDay(today()->addMonthsNoOverflow(3)->subDay()));
    }

    public function test_la_renovacion_empieza_al_dia_siguiente_de_que_termina_la_vigente(): void
    {
        $negocio = $this->negocio();
        $vigente = $negocio->licencias->first();
        $this->actingAs($this->superAdmin());

        Livewire::test(Detalle::class, ['negocio' => $negocio])
            ->call('abrirRenovacion')
            ->set('meses', 12)
            ->call('renovar')
            ->assertHasNoErrors();

        $nueva = Licencia::where('negocio_id', $negocio->id)->latest('id')->first();

        $this->assertTrue($nueva->fecha_inicio->isSameDay($vigente->fecha_fin->copy()->addDay()));
        $this->assertSame(EstadoLicencia::Programada, $nueva->estadoActual());
    }

    public function test_suspender_la_licencia_bloquea_al_dueno(): void
    {
        $negocio = $this->negocio();
        $this->actingAs($this->superAdmin());

        Livewire::test(Detalle::class, ['negocio' => $negocio])->call('suspender', $negocio->licencias->first()->id);

        $this->actingAs($negocio->dueno)
            ->get(route('negocio.inicio'))
            ->assertRedirect(route('licencia.inactiva'));
    }
}
