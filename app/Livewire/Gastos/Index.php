<?php

namespace App\Livewire\Gastos;

use App\Enums\CategoriaGasto;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Gasto;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gastos.
 *  - Encargado: gastos de su restaurante.
 *  - Dueño: gastos de todo su negocio, con filtro por restaurante.
 */
class Index extends Component
{
    use AlcanceDelNegocio;
    use WithPagination;

    #[Url(except: '')]
    public string $desde = '';

    #[Url(except: '')]
    public string $hasta = '';

    #[Url(except: '')]
    public string $restaurante = '';

    #[Url(except: '')]
    public string $categoria = '';

    #[Url(as: 'q', except: '')]
    public string $buscar = '';

    public function mount(): void
    {
        $this->desde = $this->desde ?: today()->startOfMonth()->toDateString();
        $this->hasta = $this->hasta ?: today()->toDateString();
    }

    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['desde', 'hasta', 'restaurante', 'categoria', 'buscar'], true)) {
            $this->resetPage();
        }
    }

    public function periodo(string $cual): void
    {
        [$this->desde, $this->hasta] = match ($cual) {
            'semana' => [today()->startOfWeek()->toDateString(), today()->toDateString()],
            'mes-pasado' => [today()->subMonthNoOverflow()->startOfMonth()->toDateString(), today()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'hoy' => [today()->toDateString(), today()->toDateString()],
            default => [today()->startOfMonth()->toDateString(), today()->toDateString()],
        };
        $this->resetPage();
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function rango(): array
    {
        $desde = Carbon::parse($this->desde ?: today()->startOfMonth());
        $hasta = Carbon::parse($this->hasta ?: today());

        return [$desde->startOfDay(), max($hasta, $desde)->endOfDay()];
    }

    private function filtroRestaurante(Builder $q): Builder
    {
        return $q->when(! $this->esEncargado() && $this->restaurante !== '', fn (Builder $w) => $w->where('restaurante_id', (int) $this->restaurante));
    }

    private function consultaFiltrada(): Builder
    {
        [$desde, $hasta] = $this->rango();
        $like = '%'.mb_strtolower(trim($this->buscar)).'%';

        return $this->filtroRestaurante($this->consultaGastos())
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when(CategoriaGasto::tryFrom($this->categoria), fn (Builder $q, CategoriaGasto $c) => $q->where('categoria', $c))
            ->when($this->buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereRaw('LOWER(concepto) LIKE ?', [$like])
                ->orWhereRaw('LOWER(proveedor) LIKE ?', [$like])));
    }

    #[Computed]
    public function gastos(): LengthAwarePaginator
    {
        return $this->consultaFiltrada()
            ->with(['restaurante', 'registradoPor'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(15);
    }

    /** Totales del periodo, por categoría y comparación contra las ventas. */
    #[Computed]
    public function resumen(): array
    {
        [$desde, $hasta] = $this->rango();

        $gastos = $this->consultaFiltrada()->get(['categoria', 'monto']);
        $total = (float) $gastos->sum('monto');

        $porCategoria = collect(CategoriaGasto::cases())
            ->map(fn (CategoriaGasto $c) => [
                'categoria' => $c,
                'monto' => (float) $gastos->where('categoria', $c)->sum('monto'),
            ])
            ->filter(fn ($fila) => $fila['monto'] > 0)
            ->sortByDesc('monto')
            ->values();

        // Ventas del mismo periodo y restaurante(s), sin filtro de categoría.
        $ventas = (float) $this->filtroRestaurante($this->consultaVentas())
            ->completadas()
            ->whereBetween('created_at', [$desde, $hasta])
            ->sum('total');

        return [
            'total' => $total,
            'registros' => $gastos->count(),
            'porCategoria' => $porCategoria,
            'ventas' => $ventas,
            'utilidad' => $ventas - $total,
            'proporcion' => $ventas > 0 ? (int) round($total / $ventas * 100) : null,
        ];
    }

    public function eliminar(int $id): void
    {
        /** @var Gasto $gasto */
        $gasto = $this->consultaGastos()->findOrFail($id);
        $gasto->delete();
        unset($this->gastos, $this->resumen);

        Flux::toast(variant: 'success', text: "Se eliminó el gasto «{$gasto->concepto}».");
    }

    public function render()
    {
        return view('livewire.gastos.index')->title('Gastos');
    }
}
