<?php

namespace App\Livewire\Negocio\Productos;

use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Categoria;
use App\Models\Producto;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Alta y edición de un producto del catálogo, con su disponibilidad
 * y precio especial en cada restaurante.
 */
class Formulario extends Component
{
    use AlcanceDelNegocio;
    use WithFileUploads;

    public ?Producto $producto = null;

    public string $nombre = '';

    public string $descripcion = '';

    public string $categoriaId = '';

    public string $precio = '';

    public string $costo = '';

    public bool $activo = true;

    /** @var TemporaryUploadedFile|null */
    public $imagen = null;

    public bool $quitarImagen = false;

    /**
     * Configuración por restaurante: [id => ['vende' => bool, 'precio' => string]]
     *
     * @var array<int, array{vende: bool, precio: string}>
     */
    public array $enRestaurantes = [];

    public function mount(?Producto $producto = null): void
    {
        if ($producto?->exists) {
            abort_unless($producto->negocio_id === $this->usuario()->negocio_id, 404);
            $this->producto = $producto->load('restaurantes');
            $this->fill([
                'nombre' => $producto->nombre,
                'descripcion' => (string) $producto->descripcion,
                'categoriaId' => (string) $producto->categoria_id,
                'precio' => (string) $producto->precio,
                'costo' => (string) $producto->costo,
                'activo' => $producto->activo,
            ]);
        }

        foreach ($this->restaurantesDisponibles() as $restaurante) {
            $pivote = $this->producto?->restaurantes->firstWhere('id', $restaurante->id)?->pivot;

            $this->enRestaurantes[$restaurante->id] = [
                // Un producto nuevo se ofrece por defecto en todos los restaurantes.
                'vende' => $this->producto ? $pivote !== null : true,
                'precio' => $pivote?->precio !== null ? (string) $pivote->precio : '',
            ];
        }
    }

    public function esEdicion(): bool
    {
        return $this->producto !== null;
    }

    /** @return Collection<int, Categoria> */
    #[Computed]
    public function categorias(): Collection
    {
        return Categoria::where('negocio_id', $this->usuario()->negocio_id)->orderBy('orden')->orderBy('nombre')->get();
    }

    /** Margen calculado en vivo mientras se escribe. */
    public function margen(): ?int
    {
        $precio = (float) $this->precio;
        $costo = $this->costo === '' ? null : (float) $this->costo;

        return ($costo === null || $precio <= 0) ? null : (int) round(($precio - $costo) / $precio * 100);
    }

    public function marcarTodos(bool $vende): void
    {
        foreach ($this->enRestaurantes as $id => $config) {
            $this->enRestaurantes[$id]['vende'] = $vende;
        }
    }

    protected function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:120',
                Rule::unique('productos', 'nombre')->where('negocio_id', $this->usuario()->negocio_id)->ignore($this->producto?->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'categoriaId' => ['nullable', Rule::in($this->categorias->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'precio' => ['required', 'numeric', 'min:0', 'max:999999'],
            'costo' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'activo' => ['boolean'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'enRestaurantes' => ['array'],
            'enRestaurantes.*' => ['array'],
            'enRestaurantes.*.vende' => ['boolean'],
            'enRestaurantes.*.precio' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'descripcion' => 'descripción',
            'categoriaId' => 'categoría',
            'imagen' => 'foto',
            'enRestaurantes.*.precio' => 'precio especial',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.unique' => 'Ya tienes un producto con ese nombre.',
            'imagen.max' => 'La foto no debe pesar más de 2 MB.',
        ];
    }

    public function guardar(): void
    {
        $datos = $this->validate();
        $idsPermitidos = $this->restaurantesDisponibles()->pluck('id')->all();

        DB::transaction(function () use ($datos, $idsPermitidos) {
            $producto = $this->producto ?? new Producto(['negocio_id' => $this->usuario()->negocio_id]);

            $producto->fill([
                'nombre' => trim($datos['nombre']),
                'descripcion' => $datos['descripcion'] ?: null,
                'categoria_id' => $datos['categoriaId'] ?: null,
                'precio' => $datos['precio'],
                'costo' => $datos['costo'] === '' ? null : $datos['costo'],
                'activo' => $datos['activo'],
            ]);

            // Foto: reemplazar, quitar o conservar.
            if ($this->imagen || $this->quitarImagen) {
                if ($producto->imagen) {
                    Storage::disk('public')->delete($producto->imagen);
                }
                $producto->imagen = $this->imagen ? $this->imagen->store('productos', 'public') : null;
            }

            $producto->save();

            // Disponibilidad por restaurante (solo restaurantes de este negocio).
            $actuales = $producto->restaurantes()->pluck('disponible', 'restaurantes.id');
            $sincronizar = collect($datos['enRestaurantes'] ?? [])
                ->filter(fn ($config, $id) => in_array((int) $id, $idsPermitidos, true) && ! empty($config['vende']))
                ->mapWithKeys(fn ($config, $id) => [(int) $id => [
                    'precio' => ($config['precio'] ?? '') === '' ? null : $config['precio'],
                    // Se respeta si el encargado ya lo había marcado como agotado.
                    'disponible' => $actuales[(int) $id] ?? true,
                ]])
                ->all();

            $producto->restaurantes()->sync($sincronizar);
            $this->producto = $producto;
        });

        Flux::toast(variant: 'success', text: "Se guardó «{$this->producto->nombre}».");
        $this->redirectRoute('negocio.productos.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.negocio.productos.formulario')
            ->title($this->esEdicion() ? 'Editar producto' : 'Nuevo producto');
    }
}
