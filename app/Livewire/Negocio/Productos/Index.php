<?php

namespace App\Livewire\Negocio\Productos;

use App\Events\MenuActualizado;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Categoria;
use App\Models\Producto;
use App\Support\TiempoReal;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Productos')]
class Index extends Component
{
    use AlcanceDelNegocio;
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $buscar = '';

    #[Url(except: '')]
    public string $categoria = '';

    #[Url(except: '')]
    public string $restaurante = '';

    public function updated(string $propiedad): void
    {
        if (in_array($propiedad, ['buscar', 'categoria', 'restaurante'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset('buscar', 'categoria', 'restaurante');
        $this->resetPage();
    }

    /** @return Collection<int, Categoria> */
    #[Computed]
    public function categorias(): Collection
    {
        return Categoria::where('negocio_id', $this->usuario()->negocio_id)->orderBy('orden')->orderBy('nombre')->get();
    }

    #[Computed]
    public function totalRestaurantes(): int
    {
        return $this->consultaRestaurantes()->count();
    }

    #[Computed]
    public function productos(): LengthAwarePaginator
    {
        return Producto::query()
            ->delNegocio($this->usuario()->negocio_id)
            ->with(['categoria', 'restaurantes'])
            ->buscar($this->buscar)
            ->when($this->categoria === 'sin', fn (Builder $q) => $q->whereNull('categoria_id'))
            ->when(ctype_digit($this->categoria), fn (Builder $q) => $q->where('categoria_id', (int) $this->categoria))
            ->when($this->restaurante !== '', fn (Builder $q) => $q->whereHas('restaurantes', fn (Builder $r) => $r->whereKey((int) $this->restaurante)))
            ->orderBy('nombre')
            ->paginate(12);
    }

    private function buscarProducto(int $id): Producto
    {
        return Producto::delNegocio($this->usuario()->negocio_id)->findOrFail($id);
    }

    public function alternarActivo(int $id): void
    {
        $producto = $this->buscarProducto($id);
        $producto->update(['activo' => ! $producto->activo]);
        unset($this->productos);
        $this->avisarMenu($producto);

        Flux::toast(
            variant: 'success',
            text: $producto->activo ? "«{$producto->nombre}» vuelve a aparecer en el menú." : "«{$producto->nombre}» ya no aparece en ningún restaurante.",
        );
    }

    public function duplicar(int $id): void
    {
        $original = $this->buscarProducto($id)->load('restaurantes');

        $nombre = $original->nombre.' (copia)';
        $n = 2;
        while (Producto::delNegocio($this->usuario()->negocio_id)->where('nombre', $nombre)->exists()) {
            $nombre = $original->nombre." (copia {$n})";
            $n++;
        }

        $copia = $original->replicate(['imagen']);
        $copia->nombre = $nombre;
        $copia->save();
        $copia->restaurantes()->sync(
            $original->restaurantes->mapWithKeys(fn ($r) => [$r->id => ['precio' => $r->pivot->precio, 'disponible' => true]])->all()
        );

        Flux::toast(variant: 'success', text: "Se creó «{$copia->nombre}». Edítalo para ajustar nombre y precio.");
        $this->redirectRoute('negocio.productos.editar', $copia, navigate: true);
    }

    /** Avisa a los puntos de venta de los restaurantes donde se vende el producto. */
    private function avisarMenu(Producto $producto): void
    {
        $restaurantes = $producto->restaurantes()->pluck('restaurantes.id')->all();

        if ($restaurantes !== []) {
            TiempoReal::emitir(new MenuActualizado($restaurantes));
        }
    }

    public function eliminar(int $id): void
    {
        $producto = $this->buscarProducto($id);
        $this->avisarMenu($producto);
        $producto->delete();
        unset($this->productos);

        Flux::toast(variant: 'success', text: "Se eliminó «{$producto->nombre}».");
    }
}
