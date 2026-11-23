<?php

namespace App\Models;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $negocio_id
 * @property int $restaurante_id
 * @property int|null $vendedor_id
 * @property int $folio
 * @property string $subtotal
 * @property string $descuento
 * @property string $total
 * @property MetodoPago $metodo_pago
 * @property string|null $recibido
 * @property string|null $cambio
 * @property EstadoVenta $estado
 * @property string|null $notas
 * @property Carbon|null $cancelada_at
 * @property string|null $motivo_cancelacion
 * @property Carbon $created_at
 */
class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'negocio_id', 'restaurante_id', 'vendedor_id', 'folio', 'subtotal', 'descuento', 'total',
        'metodo_pago', 'recibido', 'cambio', 'estado', 'notas',
        'cancelada_por', 'cancelada_at', 'motivo_cancelacion',
    ];

    protected function casts(): array
    {
        return [
            'metodo_pago' => MetodoPago::class,
            'estado' => EstadoVenta::class,
            'subtotal' => 'decimal:2',
            'descuento' => 'decimal:2',
            'total' => 'decimal:2',
            'recibido' => 'decimal:2',
            'cambio' => 'decimal:2',
            'cancelada_at' => 'datetime',
        ];
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function canceladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelada_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function scopeCompletadas(Builder $query): Builder
    {
        return $query->where('estado', EstadoVenta::Completada);
    }

    /** Ventas de un día (según la zona horaria de la app). */
    public function scopeDelDia(Builder $query, Carbon|string $dia): Builder
    {
        $dia = Carbon::parse($dia);

        return $query->whereBetween('created_at', [$dia->copy()->startOfDay(), $dia->copy()->endOfDay()]);
    }

    /** Folio legible, p. ej. "000123". */
    public function folioFormateado(): string
    {
        return str_pad((string) $this->folio, 6, '0', STR_PAD_LEFT);
    }

    public function estaCancelada(): bool
    {
        return $this->estado === EstadoVenta::Cancelada;
    }

    /** Número total de piezas vendidas. */
    public function piezas(): int
    {
        return (int) $this->detalles->sum('cantidad');
    }
}
