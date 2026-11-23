<?php

namespace App\Livewire\Ventas;

use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Venta;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Ventas del vendedor en un día (por defecto, hoy).
 */
#[Title('Mis ventas')]
class MisVentas extends Component
{
    use AlcanceDelNegocio;

    #[Url(except: '')]
    public string $fecha = '';

    public ?int $abiertaId = null;

    public function mount(): void
    {
        $this->fecha = $this->fecha ?: today()->toDateString();
    }

    /** @return Collection<int, Venta> */
    #[Computed]
    public function ventas(): Collection
    {
        return $this->consultaVentas()
            ->with('detalles')
            ->delDia($this->fecha)
            ->latest()
            ->get();
    }

    #[Computed]
    public function resumen(): array
    {
        $completadas = $this->ventas->reject->estaCancelada();

        return [
            'ventas' => $completadas->count(),
            'total' => (float) $completadas->sum('total'),
            'efectivo' => (float) $completadas->where('metodo_pago', \App\Enums\MetodoPago::Efectivo)->sum('total'),
            'canceladas' => $this->ventas->count() - $completadas->count(),
        ];
    }

    public function alternar(int $id): void
    {
        $this->abiertaId = $this->abiertaId === $id ? null : $id;
    }

    public function updatedFecha(): void
    {
        $this->abiertaId = null;
        unset($this->ventas, $this->resumen);
    }

    public function render()
    {
        return view('livewire.ventas.mis-ventas');
    }
}
