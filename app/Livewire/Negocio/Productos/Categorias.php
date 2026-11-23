<?php

namespace App\Livewire\Negocio\Productos;

use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Categoria;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Categorías del menú: alta, renombrar, ordenar y eliminar, todo en la misma pantalla.
 */
#[Title('Categorías')]
class Categorias extends Component
{
    use AlcanceDelNegocio;

    public string $nueva = '';

    public ?int $editandoId = null;

    public string $editandoNombre = '';

    /** @return Collection<int, Categoria> */
    #[Computed]
    public function categorias(): Collection
    {
        return Categoria::where('negocio_id', $this->usuario()->negocio_id)
            ->withCount('productos')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    private function reglaNombre(?int $ignorar = null): array
    {
        return [
            'required', 'string', 'max:80',
            Rule::unique('categorias', 'nombre')->where('negocio_id', $this->usuario()->negocio_id)->ignore($ignorar),
        ];
    }

    public function agregar(): void
    {
        $this->validate(['nueva' => $this->reglaNombre()], ['nueva.unique' => 'Ya tienes una categoría con ese nombre.'], ['nueva' => 'nombre']);

        Categoria::create([
            'negocio_id' => $this->usuario()->negocio_id,
            'nombre' => trim($this->nueva),
            'orden' => (int) $this->categorias->max('orden') + 1,
        ]);

        $this->reset('nueva');
        unset($this->categorias);
        Flux::toast(variant: 'success', text: 'Categoría agregada.');
    }

    public function editar(int $id): void
    {
        $categoria = $this->buscar($id);
        $this->editandoId = $categoria->id;
        $this->editandoNombre = $categoria->nombre;
        $this->resetValidation();
    }

    public function cancelarEdicion(): void
    {
        $this->reset('editandoId', 'editandoNombre');
    }

    public function guardarEdicion(): void
    {
        $categoria = $this->buscar($this->editandoId);

        $this->validate(
            ['editandoNombre' => $this->reglaNombre($categoria->id)],
            ['editandoNombre.unique' => 'Ya tienes una categoría con ese nombre.'],
            ['editandoNombre' => 'nombre'],
        );

        $categoria->update(['nombre' => trim($this->editandoNombre)]);
        $this->cancelarEdicion();
        unset($this->categorias);
        Flux::toast(variant: 'success', text: 'Categoría actualizada.');
    }

    /** Mueve una categoría una posición arriba (-1) o abajo (+1) en el menú. */
    public function mover(int $id, int $direccion): void
    {
        $lista = $this->categorias->values();
        $indice = $lista->search(fn (Categoria $c) => $c->id === $id);
        $destino = $indice + $direccion;

        if ($indice === false || $destino < 0 || $destino >= $lista->count()) {
            return;
        }

        $ordenados = $lista->all();
        [$ordenados[$indice], $ordenados[$destino]] = [$ordenados[$destino], $ordenados[$indice]];

        foreach ($ordenados as $posicion => $categoria) {
            $categoria->update(['orden' => $posicion + 1]);
        }

        unset($this->categorias);
    }

    public function eliminar(int $id): void
    {
        $categoria = $this->buscar($id);
        $productos = $categoria->productos()->count();
        $categoria->delete();
        unset($this->categorias);

        Flux::toast(
            variant: 'success',
            text: $productos > 0
                ? "Se eliminó «{$categoria->nombre}». Sus {$productos} productos quedaron sin categoría."
                : "Se eliminó «{$categoria->nombre}».",
        );
    }

    private function buscar(?int $id): Categoria
    {
        return Categoria::where('negocio_id', $this->usuario()->negocio_id)->findOrFail($id);
    }
}
