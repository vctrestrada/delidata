<?php

namespace App\Livewire\Admin\Restaurantes;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use App\Models\Licencia;
use App\Models\Restaurante;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Detalle de un restaurante: datos, dueño, licencia actual e historial.
 * Desde aquí se renueva, suspende, reactiva o cancela la licencia.
 */
class Detalle extends Component
{
    public Restaurante $restaurante;

    public bool $mostrarRenovacion = false;

    // Formulario de renovación
    public string $plan = '';

    public int $meses = 12;

    public string $precio = '';

    public string $notas = '';

    public function mount(Restaurante $restaurante): void
    {
        $this->restaurante = $restaurante;
        $this->recargar();
    }

    private function recargar(): void
    {
        $this->restaurante->load(['dueno', 'licencias'])->loadCount('usuarios');
    }

    /*
    |--------------------------------------------------------------------------
    | Renovación
    |--------------------------------------------------------------------------
    */

    public function abrirRenovacion(): void
    {
        $actual = $this->restaurante->licenciaActual();

        $this->plan = ($actual?->plan ?? PlanLicencia::Profesional)->value;
        $this->meses = 12;
        $this->notas = '';
        $this->calcularPrecio();
        $this->resetValidation();
        $this->mostrarRenovacion = true;
    }

    public function cerrarRenovacion(): void
    {
        $this->mostrarRenovacion = false;
    }

    public function updatedPlan(): void
    {
        $this->calcularPrecio();
    }

    public function updatedMeses(): void
    {
        $this->calcularPrecio();
    }

    private function calcularPrecio(): void
    {
        $plan = PlanLicencia::tryFrom($this->plan) ?? PlanLicencia::Profesional;
        $this->precio = number_format($plan->precioMensual() * max(1, $this->meses), 2, '.', '');
    }

    /**
     * La renovación empieza al día siguiente de que termine la licencia vigente
     * (o la última programada). Si no hay ninguna vigente, empieza hoy.
     */
    public function inicioRenovacion(): Carbon
    {
        $ultima = $this->restaurante->licencias
            ->filter(fn (Licencia $l) => in_array($l->estadoActual(), [
                EstadoLicencia::Activa, EstadoLicencia::PorVencer, EstadoLicencia::Programada,
            ], true))
            ->sortByDesc('fecha_fin')
            ->first();

        return $ultima ? $ultima->fecha_fin->copy()->addDay() : today();
    }

    public function finRenovacion(): Carbon
    {
        return $this->inicioRenovacion()->addMonthsNoOverflow(max(1, $this->meses))->subDay();
    }

    public function renovar(): void
    {
        $datos = $this->validate([
            'plan' => ['required', Rule::enum(PlanLicencia::class)],
            'meses' => ['required', 'integer', Rule::in([1, 3, 6, 12, 24])],
            'precio' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'notas' => ['nullable', 'string', 'max:500'],
        ], attributes: ['notas' => 'notas']);

        $plan = PlanLicencia::from($datos['plan']);
        $inicio = $this->inicioRenovacion();

        $this->restaurante->licencias()->create([
            'plan' => $plan,
            'max_sucursales' => $plan->maxSucursales(),
            'precio' => $datos['precio'],
            'fecha_inicio' => $inicio,
            'fecha_fin' => $this->finRenovacion(),
            'estado' => EstadoLicencia::Activa,
            'notas' => $datos['notas'] ?: 'Renovación',
        ]);

        $this->mostrarRenovacion = false;
        $this->recargar();

        Flux::toast(
            variant: 'success',
            heading: 'Licencia renovada',
            text: $inicio->isFuture()
                ? 'La renovación empezará el '.$inicio->translatedFormat('d \d\e F').'.'
                : 'La licencia ya está activa.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cambios de estado
    |--------------------------------------------------------------------------
    */

    public function suspender(int $licenciaId): void
    {
        $this->cambiarEstado($licenciaId, EstadoLicencia::Suspendida, 'Licencia suspendida. El personal del restaurante ya no podrá entrar.');
    }

    public function reactivar(int $licenciaId): void
    {
        $this->cambiarEstado($licenciaId, EstadoLicencia::Activa, 'Licencia reactivada.');
    }

    public function cancelar(int $licenciaId): void
    {
        $this->cambiarEstado($licenciaId, EstadoLicencia::Cancelada, 'Licencia cancelada.');
    }

    private function cambiarEstado(int $licenciaId, EstadoLicencia $estado, string $mensaje): void
    {
        $this->restaurante->licencias()->whereKey($licenciaId)->firstOrFail()->update(['estado' => $estado]);
        $this->recargar();

        Flux::toast(variant: 'success', text: $mensaje);
    }

    public function alternarActivo(): void
    {
        $this->restaurante->update(['activo' => ! $this->restaurante->activo]);
        $this->recargar();

        Flux::toast(
            variant: 'success',
            text: $this->restaurante->activo ? 'Restaurante activado.' : 'Restaurante desactivado. Su personal ya no podrá entrar.',
        );
    }

    public function render()
    {
        return view('livewire.admin.restaurantes.detalle')->title($this->restaurante->nombre);
    }
}
