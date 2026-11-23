<?php

namespace App\Livewire\Encargado;

use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Producto;
use App\Models\Restaurante;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Menú del restaurante del encargado: ve precios y marca productos como agotados.
 * Los precios y el catálogo solo los cambia el dueño.
 */
#[Title('Menú')]
class Menu extends Component
{
    use AlcanceDelNegocio;

    public string $buscar = '';

    #[Computed]
    public function restaurante(): Restaurante
    {
        return $this->usuario()->restaurante()->firstOrFail();
    }

    /** Productos agrupados por categoría, respetando el orden de categorías. @return Collection<string, Collection<int, Producto>> */
    #[Computed]
    public function menu(): Collection
    {
        return $this->restaurante->productos()
            ->with('categoria')
            ->where('productos.activo', true)
            ->buscar($this->buscar)
            ->orderBy('productos.nombre')
            ->get()
            ->sortBy(fn (Producto $p) => [$p->categoria?->orden ?? PHP_INT_MAX, $p->categoria?->nombre ?? 'zzz'])
            ->groupBy(fn (Producto $p) => $p->categoria?->nombre ?? 'Sin categoría');
    }

    #[Computed]
    public function agotados(): int
    {
        return $this->menu->flatten()->where('pivot.disponible', false)->count();
    }

    public function alternarDisponible(int $productoId): void
    {
        $producto = $this->restaurante->productos()->whereKey($productoId)->firstOrFail();
        $disponible = ! $producto->pivot->disponible;

        $this->restaurante->productos()->updateExistingPivot($productoId, ['disponible' => $disponible]);
        unset($this->menu, $this->agotados);

        Flux::toast(
            variant: $disponible ? 'success' : 'warning',
            text: $disponible ? "«{$producto->nombre}» vuelve a estar disponible." : "«{$producto->nombre}» se marcó como agotado.",
        );
    }

    public function reactivarTodos(): void
    {
        $this->restaurante->productos()->newPivotStatement()
            ->where('restaurante_id', $this->restaurante->id)
            ->update(['disponible' => true, 'updated_at' => now()]);
        unset($this->menu, $this->agotados);

        Flux::toast(variant: 'success', text: 'Todos los productos están disponibles otra vez.');
    }
}
