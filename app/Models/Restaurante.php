<?php

namespace App\Models;

use App\Enums\Rol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Restaurante (local) que el dueño registra en su negocio.
 *
 * @property int $id
 * @property int $negocio_id
 * @property string $nombre
 * @property string|null $direccion
 * @property string|null $telefono
 * @property string|null $abre
 * @property string|null $cierra
 * @property bool $activo
 */
class Restaurante extends Model
{
    protected $table = 'restaurantes';

    protected $fillable = [
        'negocio_id', 'nombre', 'direccion', 'telefono', 'abre', 'cierra', 'activo',
    ];

    protected $attributes = [
        'activo' => true,
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    /** Encargados y vendedores que trabajan aquí. */
    public function personal(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function encargados(): HasMany
    {
        return $this->personal()->role(Rol::Encargado->value);
    }

    public function vendedores(): HasMany
    {
        return $this->personal()->role(Rol::Vendedor->value);
    }

    /** Productos del catálogo que se venden en este restaurante. */
    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'producto_restaurante')
            ->withPivot(['precio', 'disponible'])
            ->withTimestamps();
    }

    public function scopeDelNegocio(Builder $query, int $negocioId): Builder
    {
        return $query->where('negocio_id', $negocioId);
    }

    /** Horario legible, p. ej. "08:00 – 22:00". */
    public function horario(): ?string
    {
        if (! $this->abre || ! $this->cierra) {
            return null;
        }

        return Carbon::parse($this->abre)->format('H:i').' – '.Carbon::parse($this->cierra)->format('H:i');
    }
}
