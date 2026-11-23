<?php

namespace App\Enums;

/**
 * Planes de licencia que el super admin puede vender a un restaurante.
 */
enum PlanLicencia: string
{
    case Basico = 'basico';
    case Profesional = 'profesional';
    case Empresarial = 'empresarial';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Basico => 'Básico',
            self::Profesional => 'Profesional',
            self::Empresarial => 'Empresarial',
        };
    }

    /** Número máximo de sucursales que permite el plan. */
    public function maxSucursales(): int
    {
        return match ($this) {
            self::Basico => 1,
            self::Profesional => 5,
            self::Empresarial => 20,
        };
    }

    /** Precio sugerido por mes, en pesos mexicanos. */
    public function precioMensual(): float
    {
        return match ($this) {
            self::Basico => 499,
            self::Profesional => 999,
            self::Empresarial => 1999,
        };
    }

    public function descripcion(): string
    {
        $sucursales = $this->maxSucursales();

        return $sucursales === 1
            ? '1 sucursal · $'.number_format($this->precioMensual()).' al mes'
            : "Hasta {$sucursales} sucursales · $".number_format($this->precioMensual()).' al mes';
    }
}
