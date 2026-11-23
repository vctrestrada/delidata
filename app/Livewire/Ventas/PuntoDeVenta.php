<?php

namespace App\Livewire\Ventas;

use App\Actions\Ventas\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Livewire\Concerns\AlcanceDelNegocio;
use App\Models\Producto;
use App\Models\Restaurante;
use App\Models\Venta;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Punto de venta del vendedor.
 * El carrito vive en el componente; al cobrar se recalcula todo en el servidor.
 */
#[Title('Punto de venta')]
class PuntoDeVenta extends Component
{
    use AlcanceDelNegocio;

    public string $buscar = '';

    /** Id de categoría seleccionada ('' = todas). */
    public string $categoria = '';

    /** @var array<int, int> [producto_id => cantidad] */
    public array $carrito = [];

    public string $metodo = 'efectivo';

    public string $recibido = '';

    public string $descuento = '';

    public string $notas = '';

    /** Venta recién cobrada (para mostrar el resumen y el ticket). */
    public ?int $ultimaVentaId = null;

    #[Computed]
    public function restaurante(): Restaurante
    {
        return $this->usuario()->restaurante()->firstOrFail();
    }

    /** Productos que se pueden vender ahora (activos y no agotados). @return Collection<int, Producto> */
    #[Computed]
    public function productosDisponibles(): Collection
    {
        return $this->restaurante->productos()
            ->with('categoria')
            ->where('productos.activo', true)
            ->wherePivot('disponible', true)
            ->orderBy('productos.nombre')
            ->get()
            ->sortBy(fn (Producto $p) => sprintf('%05d-%s', $p->categoria?->orden ?? 99999, $p->nombre))
            ->values();
    }

    /** @return Collection<int, \App\Models\Categoria> */
    #[Computed]
    public function categorias(): Collection
    {
        return $this->productosDisponibles->pluck('categoria')->filter()->unique('id')->sortBy('orden')->values();
    }

    /** @return Collection<int, Producto> */
    #[Computed]
    public function catalogo(): Collection
    {
        $termino = mb_strtolower(trim($this->buscar));

        return $this->productosDisponibles
            ->when($this->categoria !== '', fn ($c) => $c->where('categoria_id', (int) $this->categoria))
            ->when($termino !== '', fn ($c) => $c->filter(fn (Producto $p) => str_contains(mb_strtolower($p->nombre), $termino)));
    }

    private function precioDe(Producto $producto): float
    {
        return (float) ($producto->pivot->precio ?? $producto->precio);
    }

    /**
     * Renglones del carrito con precio e importe (solo informativo; el cobro se recalcula).
     *
     * @return Collection<int, array{producto: Producto, cantidad: int, precio: float, importe: float}>
     */
    #[Computed]
    public function renglones(): Collection
    {
        $productos = $this->productosDisponibles->keyBy('id');

        return collect($this->carrito)
            ->filter(fn ($cantidad, $id) => $productos->has($id))
            ->map(fn ($cantidad, $id) => [
                'producto' => $productos[$id],
                'cantidad' => $cantidad,
                'precio' => $this->precioDe($productos[$id]),
                'importe' => round($this->precioDe($productos[$id]) * $cantidad, 2),
            ]);
    }

    /** @return array{subtotal: float, descuento: float, total: float, piezas: int, cambio: float|null} */
    #[Computed]
    public function totales(): array
    {
        $subtotal = round($this->renglones->sum('importe'), 2);
        $descuento = round(max(0, min((float) $this->descuento, $subtotal)), 2);
        $total = round($subtotal - $descuento, 2);
        $recibido = $this->recibido === '' ? null : (float) $this->recibido;

        return [
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'total' => $total,
            'piezas' => (int) $this->renglones->sum('cantidad'),
            'cambio' => $this->metodo === 'efectivo' && $recibido !== null ? round($recibido - $total, 2) : null,
        ];
    }

    #[Computed]
    public function ultimaVenta(): ?Venta
    {
        return $this->ultimaVentaId
            ? $this->consultaVentas()->with('detalles')->find($this->ultimaVentaId)
            : null;
    }

    #[Computed]
    public function resumenDelDia(): array
    {
        $ventas = $this->consultaVentas()->completadas()->delDia(today())->get(['total']);

        return ['ventas' => $ventas->count(), 'total' => (float) $ventas->sum('total')];
    }

    /*
    |--------------------------------------------------------------------------
    | Carrito
    |--------------------------------------------------------------------------
    */

    public function agregar(int $productoId): void
    {
        if (! $this->productosDisponibles->contains('id', $productoId)) {
            Flux::toast(variant: 'warning', text: 'Ese producto ya no está disponible.');

            return;
        }

        $this->ultimaVentaId = null;
        $this->carrito[$productoId] = ($this->carrito[$productoId] ?? 0) + 1;
    }

    public function restar(int $productoId): void
    {
        if (! isset($this->carrito[$productoId])) {
            return;
        }

        $this->carrito[$productoId]--;

        if ($this->carrito[$productoId] <= 0) {
            unset($this->carrito[$productoId]);
        }
    }

    public function quitar(int $productoId): void
    {
        unset($this->carrito[$productoId]);
    }

    public function vaciar(): void
    {
        $this->reset('carrito', 'recibido', 'descuento', 'notas');
        $this->metodo = MetodoPago::Efectivo->value;
    }

    /** Botones rápidos de efectivo: exacto o un billete. */
    public function recibir(?float $monto = null): void
    {
        $this->recibido = number_format($monto ?? $this->totales['total'], 2, '.', '');
    }

    /** Billetes sugeridos según el total. @return list<int> */
    public function billetesSugeridos(): array
    {
        $total = $this->totales['total'];

        return collect([20, 50, 100, 200, 500, 1000])
            ->filter(fn ($b) => $b > $total)
            ->take(3)
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Cobro
    |--------------------------------------------------------------------------
    */

    public function cobrar(RegistrarVenta $registrar): void
    {
        $this->validate([
            'metodo' => ['required', 'in:'.implode(',', array_column(MetodoPago::cases(), 'value'))],
            'recibido' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'descuento' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'notas' => ['nullable', 'string', 'max:255'],
        ], attributes: ['recibido' => 'efectivo recibido']);

        $venta = $registrar(
            vendedor: $this->usuario(),
            carrito: $this->carrito,
            metodo: MetodoPago::from($this->metodo),
            recibido: $this->recibido === '' ? null : (float) $this->recibido,
            descuento: (float) ($this->descuento ?: 0),
            notas: $this->notas,
        );

        $this->vaciar();
        $this->ultimaVentaId = $venta->id;
        unset($this->resumenDelDia);

        Flux::toast(
            variant: 'success',
            heading: 'Venta #'.$venta->folioFormateado(),
            text: $venta->cambio !== null && (float) $venta->cambio > 0
                ? 'Entrega $'.number_format((float) $venta->cambio, 2).' de cambio.'
                : 'Cobro registrado.',
        );
    }

    public function nuevaVenta(): void
    {
        $this->ultimaVentaId = null;
    }
}
