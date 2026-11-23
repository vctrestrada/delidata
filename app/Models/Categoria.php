<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $negocio_id
 * @property string $nombre
 * @property int $orden
 */
class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = ['negocio_id', 'nombre', 'orden'];

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}
