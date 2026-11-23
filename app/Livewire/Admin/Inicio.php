<?php

namespace App\Livewire\Admin;

use App\Enums\EstadoLicencia;
use App\Models\Restaurante;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Administración')]
class Inicio extends Component
{
    /** @return Collection<int, Restaurante> */
    #[Computed]
    public function restaurantes(): Collection
    {
        return Restaurante::with(['dueno', 'licencias'])->orderBy('nombre')->get();
    }

    /** @return array<string, int|float> */
    #[Computed]
    public function resumen(): array
    {
        $estados = $this->restaurantes->map(fn (Restaurante $r) => $r->estadoLicencia());

        $ingresoVigente = $this->restaurantes
            ->filter(fn (Restaurante $r) => $r->estadoLicencia()->permiteAcceso())
            ->sum(fn (Restaurante $r) => (float) $r->licenciaActual()?->precio);

        return [
            'total' => $this->restaurantes->count(),
            'vigentes' => $estados->filter(fn (EstadoLicencia $e) => $e->permiteAcceso())->count(),
            'porVencer' => $estados->filter(fn (EstadoLicencia $e) => $e === EstadoLicencia::PorVencer)->count(),
            'sinAcceso' => $estados->reject(fn (EstadoLicencia $e) => $e->permiteAcceso())->count(),
            'ingresoVigente' => $ingresoVigente,
        ];
    }

    /**
     * Restaurantes que requieren atención: por vencer o sin acceso.
     *
     * @return Collection<int, Restaurante>
     */
    #[Computed]
    public function atencion(): Collection
    {
        return $this->restaurantes
            ->filter(fn (Restaurante $r) => in_array($r->estadoLicencia(), [
                EstadoLicencia::PorVencer, EstadoLicencia::Vencida, EstadoLicencia::Suspendida, EstadoLicencia::SinLicencia,
            ], true))
            ->sortBy(fn (Restaurante $r) => $r->licenciaActual()?->fecha_fin?->timestamp ?? 0)
            ->take(6);
    }
}
