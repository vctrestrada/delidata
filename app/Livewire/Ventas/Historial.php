<?php

namespace App\Livewire\Ventas;

use App\Actions\Ventas\CancelarVenta;
use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\Rol;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Livewire\Concerns\EscuchaTiempoReal;
use App\Models\User;
use App\Models\Venta;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Historial de ventas.
 *  - Encargado: ventas de su restaurante.
 *  - Dueño: ventas de todo su negocio, con filtro por restaurante.
 * Ambos pueden cancelar ventas indicando el motivo.
 */
class Historial extends Component
{
    use AlcanceDelNegocio;
    use EscuchaTiempoReal;
    use WithPagination;

    #[Url(except: '')]
    public string $desde = '';

    #[Url(except: '')]
    public string $hasta = '';

    #[Url(except: '')]
    public string $restaurante = '';

    #[Url(except: '')]
    public string $vendedor = '';

    #[Url(except: '')]
    public string $metodo = '';

    #[Url(except: '')]
    public string $estado = '';

    public ?int $abiertaId = null;

    public ?int $cancelandoId = null;

    public string $motivo = '';

    public function mount(): void
    {
        $this->desde = $this->desde ?: today()->toDateString();
        $this->hasta = $this->hasta ?: today()->toDateString();
    }

    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['desde', 'hasta', 'restaurante', 'vendedor', 'metodo', 'estado'], true)) {
            $this->resetPage();
            $this->reset('abiertaId', 'cancelandoId');
        }
    }

    /** Atajos de periodo: hoy, ayer, semana, mes. */
    public function periodo(string $cual): void
    {
        [$this->desde, $this->hasta] = match ($cual) {
            'ayer' => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
            'semana' => [today()->startOfWeek()->toDateString(), today()->toDateString()],
            'mes' => [today()->startOfMonth()->toDateString(), today()->toDateString()],
            default => [today()->toDateString(), today()->toDateString()],
        };
        $this->resetPage();
    }

    /** Consulta con todos los filtros aplicados (sin paginar). */
    private function consultaFiltrada(): Builder
    {
        $desde = $this->desde ?: today()->toDateString();
        $hasta = max($this->hasta ?: $desde, $desde);

        return $this->consultaVentas()
            ->whereBetween('created_at', [
                \Illuminate\Support\Carbon::parse($desde)->startOfDay(),
                \Illuminate\Support\Carbon::parse($hasta)->endOfDay(),
            ])
            ->when(! $this->esEncargado() && $this->restaurante !== '', fn (Builder $q) => $q->where('restaurante_id', (int) $this->restaurante))
            ->when($this->vendedor !== '', fn (Builder $q) => $q->where('vendedor_id', (int) $this->vendedor))
            ->when(MetodoPago::tryFrom($this->metodo), fn (Builder $q, MetodoPago $m) => $q->where('metodo_pago', $m))
            ->when(EstadoVenta::tryFrom($this->estado), fn (Builder $q, EstadoVenta $e) => $q->where('estado', $e));
    }

    #[Computed]
    public function ventas(): LengthAwarePaginator
    {
        return $this->consultaFiltrada()
            ->with(['detalles', 'vendedor', 'restaurante', 'canceladaPor'])
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function resumen(): array
    {
        $ventas = $this->consultaFiltrada()->get(['id', 'total', 'metodo_pago', 'estado']);
        $completadas = $ventas->where('estado', EstadoVenta::Completada);
        $total = (float) $completadas->sum('total');

        return [
            'total' => $total,
            'ventas' => $completadas->count(),
            'promedio' => $completadas->count() ? $total / $completadas->count() : 0,
            'canceladas' => $ventas->count() - $completadas->count(),
            'porMetodo' => collect(MetodoPago::cases())->mapWithKeys(fn (MetodoPago $m) => [
                $m->value => (float) $completadas->where('metodo_pago', $m)->sum('total'),
            ]),
        ];
    }

    /** Vendedores para el filtro. @return Collection<int, User> */
    #[Computed]
    public function vendedores(): Collection
    {
        return User::query()
            ->where('negocio_id', $this->usuario()->negocio_id)
            ->role(Rol::Vendedor->value)
            ->when($this->esEncargado(), fn (Builder $q) => $q->where('restaurante_id', $this->usuario()->restaurante_id))
            ->when(! $this->esEncargado() && $this->restaurante !== '', fn (Builder $q) => $q->where('restaurante_id', (int) $this->restaurante))
            ->orderBy('name')
            ->get();
    }

    public function alternar(int $id): void
    {
        $this->abiertaId = $this->abiertaId === $id ? null : $id;
        $this->reset('cancelandoId', 'motivo');
    }

    public function iniciarCancelacion(int $id): void
    {
        $this->cancelandoId = $id;
        $this->abiertaId = $id;
        $this->motivo = '';
        $this->resetValidation();
    }

    public function confirmarCancelacion(CancelarVenta $cancelar): void
    {
        $this->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']], attributes: ['motivo' => 'motivo']);

        /** @var Venta $venta */
        $venta = $this->consultaVentas()->findOrFail($this->cancelandoId);
        $cancelar($venta, $this->usuario(), $this->motivo);

        $this->reset('cancelandoId', 'motivo');
        unset($this->ventas, $this->resumen);

        Flux::toast(variant: 'success', text: "La venta #{$venta->folioFormateado()} se canceló.");
    }

    protected function eventosTiempoReal(): array
    {
        return ['venta.registrada', 'venta.cancelada'];
    }

    public function render()
    {
        return view('livewire.ventas.historial')->title('Ventas');
    }
}
