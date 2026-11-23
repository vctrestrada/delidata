<?php

namespace Tests\Feature\Delidata;

use App\Enums\CategoriaGasto;
use App\Enums\Rol;
use App\Livewire\Gastos\Formulario;
use App\Models\Gasto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreaEscenario;
use Tests\TestCase;

class GastosTest extends TestCase
{
    use CreaEscenario;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrarRoles();
        Storage::fake(Gasto::DISCO_COMPROBANTES);
    }

    public function test_el_encargado_registra_un_gasto_con_comprobante_privado(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        $this->actingAs($this->personal(Rol::Encargado, $restaurante));

        Livewire::test(Formulario::class)
            ->set('categoria', CategoriaGasto::Insumos->value)
            ->set('concepto', '10 kg de pastor')
            ->set('monto', '1850.50')
            ->set('comprobante', UploadedFile::fake()->image('ticket.jpg'))
            ->call('guardar')
            ->assertHasNoErrors();

        $gasto = Gasto::firstOrFail();
        $this->assertSame($restaurante->id, $gasto->restaurante_id);
        Storage::disk(Gasto::DISCO_COMPROBANTES)->assertExists($gasto->comprobante);
    }

    public function test_no_permite_gastos_con_fecha_futura(): void
    {
        $negocio = $this->negocio();
        $this->actingAs($this->personal(Rol::Encargado, $this->restaurante($negocio)));

        Livewire::test(Formulario::class)
            ->set('concepto', 'Gas')
            ->set('monto', '500')
            ->set('fecha', today()->addDay()->toDateString())
            ->call('guardar')
            ->assertHasErrors(['fecha']);
    }

    public function test_el_comprobante_solo_lo_ve_el_negocio_dueno(): void
    {
        $negocio = $this->negocio();
        $restaurante = $this->restaurante($negocio);
        Storage::disk(Gasto::DISCO_COMPROBANTES)->put('comprobantes/1/ticket.pdf', 'pdf');

        $gasto = Gasto::create([
            'negocio_id' => $negocio->id, 'restaurante_id' => $restaurante->id, 'fecha' => today(),
            'categoria' => CategoriaGasto::Servicios, 'concepto' => 'Luz', 'monto' => 900,
            'metodo_pago' => 'tarjeta', 'comprobante' => 'comprobantes/1/ticket.pdf',
        ]);

        $this->actingAs($negocio->dueno)->get(route('gastos.comprobante', $gasto))->assertOk();
        $this->actingAs($this->negocio()->dueno)->get(route('gastos.comprobante', $gasto))->assertNotFound();
        $this->actingAs($this->personal(Rol::Vendedor, $restaurante))->get(route('gastos.comprobante', $gasto))->assertForbidden();
    }
}
