<?php

namespace App\Enums;

enum MetodoPago: string
{
    case Efectivo = 'efectivo';
    case Tarjeta = 'tarjeta';
    case Transferencia = 'transferencia';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Tarjeta => 'Tarjeta',
            self::Transferencia => 'Transferencia',
        };
    }

    public function icono(): string
    {
        return match ($this) {
            self::Efectivo => 'banknotes',
            self::Tarjeta => 'credit-card',
            self::Transferencia => 'device-phone-mobile',
        };
    }
}
