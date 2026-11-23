<?php

namespace App\Enums;

/**
 * Estados de una licencia.
 *
 * En la base de datos solo se guardan Activa, Suspendida y Cancelada
 * (decisiones del super admin). Programada, PorVencer, Vencida y
 * SinLicencia se calculan con las fechas.
 */
enum EstadoLicencia: string
{
    case Activa = 'activa';
    case PorVencer = 'por_vencer';
    case Programada = 'programada';
    case Vencida = 'vencida';
    case Suspendida = 'suspendida';
    case Cancelada = 'cancelada';
    case SinLicencia = 'sin_licencia';

    /** Días antes del vencimiento en que una licencia se marca "por vencer". */
    public const DIAS_AVISO = 15;

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::PorVencer => 'Por vencer',
            self::Programada => 'Programada',
            self::Vencida => 'Vencida',
            self::Suspendida => 'Suspendida',
            self::Cancelada => 'Cancelada',
            self::SinLicencia => 'Sin licencia',
        };
    }

    /** Color del badge de Flux. */
    public function color(): string
    {
        return match ($this) {
            self::Activa => 'green',
            self::PorVencer => 'orange',
            self::Programada => 'sky',
            self::Vencida => 'red',
            self::Suspendida, self::Cancelada, self::SinLicencia => 'zinc',
        };
    }

    /** ¿Permite que el restaurante use el sistema? */
    public function permiteAcceso(): bool
    {
        return in_array($this, [self::Activa, self::PorVencer], true);
    }

    /**
     * Estados que se pueden usar como filtro en el listado.
     *
     * @return list<self>
     */
    public static function filtrables(): array
    {
        return [self::Activa, self::PorVencer, self::Programada, self::Vencida, self::Suspendida, self::SinLicencia];
    }
}
