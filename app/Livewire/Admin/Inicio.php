<?php

namespace App\Livewire\Admin;

use App\Enums\EstadoLicencia;
use App\Models\Negocio;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Administración')]
class Inicio extends Component
{
    /** @return Collection<int, Negocio> */
    #[Computed]
    public function negocios(): Collection
    {
        return Negocio::with(['dueno', 'licencias'])->orderBy('nombre')->get();
    }

    /** @return array<string, int|float> */
    #[Computed]
    public function resumen(): array
    {
        $estados = $this->negocios->map(fn (Negocio $n) => $n->estadoLicencia());

        $ingresoVigente = $this->negocios
            ->filter(fn (Negocio $n) => $n->estadoLicencia()->permiteAcceso())
            ->sum(fn (Negocio $n) => (float) $n->licenciaActual()?->precio);

        return [
            'total' => $this->negocios->count(),
            'vigentes' => $estados->filter(fn (EstadoLicencia $e) => $e->permiteAcceso())->count(),
            'porVencer' => $estados->filter(fn (EstadoLicencia $e) => $e === EstadoLicencia::PorVencer)->count(),
            'sinAcceso' => $estados->reject(fn (EstadoLicencia $e) => $e->permiteAcceso())->count(),
            'ingresoVigente' => $ingresoVigente,
        ];
    }

    /**
     * Restaurantes que requieren atención: por vencer o sin acceso.
     *
     * @return Collection<int, Negocio>
     */
    #[Computed]
    public function atencion(): Collection
    {
        return $this->negocios
            ->filter(fn (Negocio $n) => in_array($n->estadoLicencia(), [
                EstadoLicencia::PorVencer, EstadoLicencia::Vencida, EstadoLicencia::Suspendida, EstadoLicencia::SinLicencia,
            ], true))
            ->sortBy(fn (Negocio $n) => $n->licenciaActual()?->fecha_fin?->timestamp ?? 0)
            ->take(6);
    }
}
