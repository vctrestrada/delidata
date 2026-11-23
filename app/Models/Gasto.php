<?php

namespace App\Models;

use App\Enums\CategoriaGasto;
use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $negocio_id
 * @property int $restaurante_id
 * @property int|null $registrado_por
 * @property Carbon $fecha
 * @property CategoriaGasto $categoria
 * @property string $concepto
 * @property string $monto
 * @property MetodoPago $metodo_pago
 * @property string|null $proveedor
 * @property string|null $comprobante
 * @property string|null $notas
 */
class Gasto extends Model
{
    protected $table = 'gastos';

    /** Disco privado: los comprobantes no son accesibles por URL directa. */
    public const DISCO_COMPROBANTES = 'local';

    protected $fillable = [
        'negocio_id', 'restaurante_id', 'registrado_por', 'fecha', 'categoria',
        'concepto', 'monto', 'metodo_pago', 'proveedor', 'comprobante', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'categoria' => CategoriaGasto::class,
            'metodo_pago' => MetodoPago::class,
            'monto' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (Gasto $gasto) {
            if ($gasto->comprobante) {
                Storage::disk(self::DISCO_COMPROBANTES)->delete($gasto->comprobante);
            }
        });
    }

    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function comprobanteEsPdf(): bool
    {
        return $this->comprobante !== null && str_ends_with(strtolower($this->comprobante), '.pdf');
    }
}
