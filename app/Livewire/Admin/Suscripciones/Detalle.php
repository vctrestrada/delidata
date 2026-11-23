<?php

namespace App\Livewire\Admin\Suscripciones;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use App\Models\Licencia;
use App\Models\Negocio;
use Flux\Flux;
use Carbon\CarbonInterface;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Detalle de una suscripción: datos del negocio, dueño, licencia actual e historial.
 * Desde aquí se renueva, suspende, reactiva o cancela la licencia.
 */
class Detalle extends Component
{
    public Negocio $negocio;

    public bool $mostrarRenovacion = false;

    // Formulario de renovación
    public string $plan = '';

    public int $meses = 12;

    public string $precio = '';

    public string $notas = '';

    public function mount(Negocio $negocio): void
    {
        $this->negocio = $negocio;
        $this->recargar();
    }

    private function recargar(): void
    {
        $this->negocio->load(['dueno', 'licencias'])->loadCount('usuarios');
    }

    /*
    |--------------------------------------------------------------------------
    | Renovación
    |--------------------------------------------------------------------------
    */

    public function abrirRenovacion(): void
    {
        $actual = $this->negocio->licenciaActual();

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
    public function inicioRenovacion(): CarbonInterface
    {
        $ultima = $this->negocio->licencias
            ->filter(fn (Licencia $l) => in_array($l->estadoActual(), [
                EstadoLicencia::Activa, EstadoLicencia::PorVencer, EstadoLicencia::Programada,
            ], true))
            ->sortByDesc('fecha_fin')
            ->first();

        return $ultima ? $ultima->fecha_fin->copy()->addDay() : today();
    }

    public function finRenovacion(): CarbonInterface
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

        $this->negocio->licencias()->create([
            'plan' => $plan,
            'max_restaurantes' => $plan->maxRestaurantes(),
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
        $this->cambiarEstado($licenciaId, EstadoLicencia::Suspendida, 'Licencia suspendida. El personal del negocio ya no podrá entrar.');
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
        $this->negocio->licencias()->whereKey($licenciaId)->firstOrFail()->update(['estado' => $estado]);
        $this->recargar();

        Flux::toast(variant: 'success', text: $mensaje);
    }

    public function alternarActivo(): void
    {
        $this->negocio->update(['activo' => ! $this->negocio->activo]);
        $this->recargar();

        Flux::toast(
            variant: 'success',
            text: $this->negocio->activo ? 'Negocio activado.' : 'Negocio desactivado. Su personal ya no podrá entrar.',
        );
    }

    public function render()
    {
        return view('livewire.admin.suscripciones.detalle')->title($this->negocio->nombre);
    }
}
