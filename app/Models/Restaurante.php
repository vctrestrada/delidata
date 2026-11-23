<?php

namespace App\Models;

use App\Enums\EstadoLicencia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $nombre
 * @property string $slug
 * @property string|null $rfc
 * @property string|null $telefono
 * @property string|null $correo
 * @property string|null $direccion
 * @property int|null $dueno_id
 * @property bool $activo
 */
class Restaurante extends Model
{
    protected $table = 'restaurantes';

    protected $fillable = [
        'nombre', 'slug', 'rfc', 'telefono', 'correo', 'direccion', 'dueno_id', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Genera un slug único a partir del nombre: "la-esquina", "la-esquina-2", ...
        static::saving(function (Restaurante $restaurante) {
            if (blank($restaurante->slug) || $restaurante->isDirty('nombre')) {
                $base = Str::slug($restaurante->nombre) ?: 'restaurante';
                $slug = $base;
                $n = 2;

                while (static::where('slug', $slug)->whereKeyNot($restaurante->getKey())->exists()) {
                    $slug = "{$base}-{$n}";
                    $n++;
                }

                $restaurante->slug = $slug;
            }
        });
    }

    public function dueno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dueno_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function licencias(): HasMany
    {
        return $this->hasMany(Licencia::class)->orderByDesc('fecha_inicio')->orderByDesc('id');
    }

    /**
     * Búsqueda por nombre del restaurante, RFC o datos del dueño.
     */
    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (blank($termino)) {
            return $query;
        }

        $like = '%'.mb_strtolower(trim($termino)).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw('LOWER(nombre) LIKE ?', [$like])
                ->orWhereRaw('LOWER(rfc) LIKE ?', [$like])
                ->orWhereHas('dueno', fn (Builder $d) => $d
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$like]));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Licencias (se calculan sobre la relación "licencias" ya cargada)
    |--------------------------------------------------------------------------
    */

    /** Licencia que está corriendo hoy (la última que ya inició). */
    public function licenciaActual(): ?Licencia
    {
        return $this->licencias->first(fn (Licencia $l) => $l->fecha_inicio->lte(today()));
    }

    /** Renovación activa que empieza en el futuro, si existe. */
    public function licenciaProgramada(): ?Licencia
    {
        return $this->licencias
            ->filter(fn (Licencia $l) => $l->estadoActual() === EstadoLicencia::Programada)
            ->sortBy('fecha_inicio')
            ->first();
    }

    /** Estado de licencia del restaurante para mostrar y para dar acceso. */
    public function estadoLicencia(): EstadoLicencia
    {
        $actual = $this->licenciaActual();
        $programada = $this->licenciaProgramada();

        if ($actual === null) {
            return $programada ? EstadoLicencia::Programada : EstadoLicencia::SinLicencia;
        }

        $estado = $actual->estadoActual();

        // Si ya pagó la renovación, no tiene caso avisar que está por vencer.
        if ($estado === EstadoLicencia::PorVencer && $programada) {
            return EstadoLicencia::Activa;
        }

        if ($estado === EstadoLicencia::Vencida && $programada) {
            return EstadoLicencia::Programada;
        }

        return $estado;
    }

    /** ¿El dueño y su personal pueden usar el sistema? */
    public function puedeOperar(): bool
    {
        return $this->activo && $this->estadoLicencia()->permiteAcceso();
    }
}
