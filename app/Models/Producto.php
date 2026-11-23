<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Producto del catálogo de un negocio.
 *
 * @property int $id
 * @property int $negocio_id
 * @property int|null $categoria_id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string $precio
 * @property string|null $costo
 * @property string|null $imagen
 * @property bool $activo
 */
class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'negocio_id', 'categoria_id', 'nombre', 'descripcion', 'precio', 'costo', 'imagen', 'activo',
    ];

    protected $attributes = [
        'activo' => true,
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'costo' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Al borrar el producto también se borra su imagen.
        static::deleted(function (Producto $producto) {
            if ($producto->imagen) {
                Storage::disk('public')->delete($producto->imagen);
            }
        });
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** Restaurantes donde se vende, con su precio especial y disponibilidad. */
    public function restaurantes(): BelongsToMany
    {
        return $this->belongsToMany(Restaurante::class, 'producto_restaurante')
            ->withPivot(['precio', 'disponible'])
            ->withTimestamps();
    }

    public function scopeDelNegocio(Builder $query, int $negocioId): Builder
    {
        return $query->where('negocio_id', $negocioId);
    }

    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $query;
        }

        $like = '%'.mb_strtolower(trim($termino)).'%';

        return $query->where(fn (Builder $q) => $q
            ->whereRaw('LOWER(nombre) LIKE ?', [$like])
            ->orWhereRaw('LOWER(descripcion) LIKE ?', [$like]));
    }

    /** Ganancia por unidad en porcentaje sobre el precio (null si no hay costo). */
    public function margen(?float $precio = null): ?int
    {
        $precio ??= (float) $this->precio;

        if ($this->costo === null || $precio <= 0) {
            return null;
        }

        return (int) round(($precio - (float) $this->costo) / $precio * 100);
    }

    /** Precio que se cobra en un restaurante (el especial si existe, si no el base). */
    public function precioEn(Restaurante $restaurante): float
    {
        $pivote = $this->relationLoaded('restaurantes')
            ? $this->restaurantes->firstWhere('id', $restaurante->id)?->pivot
            : $this->restaurantes()->whereKey($restaurante->id)->first()?->pivot;

        return (float) ($pivote?->precio ?? $this->precio);
    }

    public function urlImagen(): ?string
    {
        return $this->imagen ? Storage::disk('public')->url($this->imagen) : null;
    }

    /** Iniciales para la miniatura cuando no hay imagen. */
    public function iniciales(): string
    {
        return Str::upper(Str::substr(Str::of($this->nombre)->explode(' ')->map(fn ($p) => Str::substr($p, 0, 1))->take(2)->implode(''), 0, 2));
    }
}
