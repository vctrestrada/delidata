<?php

namespace App\Models;

use App\Enums\EstadoLicencia;
use App\Enums\PlanLicencia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $restaurante_id
 * @property PlanLicencia $plan
 * @property int $max_sucursales
 * @property string $precio
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property EstadoLicencia $estado
 * @property string|null $notas
 */
class Licencia extends Model
{
    protected $table = 'licencias';

    protected $fillable = [
        'restaurante_id', 'plan', 'max_sucursales', 'precio',
        'fecha_inicio', 'fecha_fin', 'estado', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'plan' => PlanLicencia::class,
            'estado' => EstadoLicencia::class,
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'precio' => 'decimal:2',
        ];
    }

    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    /**
     * Estado real de la licencia a la fecha de hoy, combinando
     * lo que decidió el super admin con las fechas de vigencia.
     */
    public function estadoActual(): EstadoLicencia
    {
        if ($this->estado !== EstadoLicencia::Activa) {
            return $this->estado;
        }

        $hoy = today();

        return match (true) {
            $this->fecha_inicio->gt($hoy) => EstadoLicencia::Programada,
            $this->fecha_fin->lt($hoy) => EstadoLicencia::Vencida,
            $this->diasRestantes() <= EstadoLicencia::DIAS_AVISO => EstadoLicencia::PorVencer,
            default => EstadoLicencia::Activa,
        };
    }

    /** Días que faltan para que termine (0 si ya terminó). */
    public function diasRestantes(): int
    {
        return max(0, (int) today()->diffInDays($this->fecha_fin, false));
    }

    /** Porcentaje del periodo que ya se consumió (para la barra de progreso). */
    public function porcentajeConsumido(): int
    {
        $total = max(1, (int) $this->fecha_inicio->diffInDays($this->fecha_fin));
        $usados = (int) $this->fecha_inicio->diffInDays(min(today(), $this->fecha_fin), false);

        return (int) max(0, min(100, round($usados / $total * 100)));
    }
}
