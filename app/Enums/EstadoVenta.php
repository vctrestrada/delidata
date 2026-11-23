<?php

namespace App\Enums;

enum EstadoVenta: string
{
    case Completada = 'completada';
    case Cancelada = 'cancelada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Completada => 'Completada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completada => 'green',
            self::Cancelada => 'red',
        };
    }
}
