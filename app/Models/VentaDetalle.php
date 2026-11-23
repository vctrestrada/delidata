<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Renglón de una venta. Guarda el nombre, precio y costo del producto
 * tal como estaban al momento de vender.
 *
 * @property int $id
 * @property int $venta_id
 * @property int|null $producto_id
 * @property string $nombre
 * @property int $cantidad
 * @property string $precio_unitario
 * @property string|null $costo_unitario
 * @property string $importe
 */
class VentaDetalle extends Model
{
    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id', 'producto_id', 'nombre', 'cantidad', 'precio_unitario', 'costo_unitario', 'importe',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'costo_unitario' => 'decimal:2',
            'importe' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
