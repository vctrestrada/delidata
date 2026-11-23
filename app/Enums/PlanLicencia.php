<?php

namespace App\Enums;

/**
 * Planes de suscripción que el super admin vende a cada negocio.
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

    /** Número máximo de restaurantes que el dueño puede registrar. */
    public function maxRestaurantes(): int
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
        $restaurantes = $this->maxRestaurantes();

        return $restaurantes === 1
            ? '1 restaurante · $'.number_format($this->precioMensual()).' al mes'
            : "Hasta {$restaurantes} restaurantes · $".number_format($this->precioMensual()).' al mes';
    }
}
