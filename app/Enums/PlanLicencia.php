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

    /** Precio anual: se pagan 10 meses y se regalan 2. */
    public function precioAnual(): float
    {
        return $this->precioMensual() * 10;
    }

    /** Para quién está pensado el plan. */
    public function lema(): string
    {
        return match ($this) {
            self::Basico => 'Para tu primer local',
            self::Profesional => 'Para negocios que están creciendo',
            self::Empresarial => 'Para cadenas y franquicias',
        };
    }

    /**
     * Lo que incluye el plan (se muestra en la página de inicio).
     *
     * @return list<string>
     */
    public function beneficios(): array
    {
        $comunes = [
            'Punto de venta y tickets',
            'Control de gastos con comprobantes',
            'Dashboard con velocímetro',
            'Ventas en tiempo real',
            'Personal ilimitado',
        ];

        return match ($this) {
            self::Basico => ['1 restaurante', ...$comunes, 'Soporte por correo'],
            self::Profesional => ['Hasta 5 restaurantes', ...$comunes, 'Comparación entre sucursales', 'Soporte por correo y WhatsApp'],
            self::Empresarial => ['Hasta 20 restaurantes', ...$comunes, 'Comparación entre sucursales', 'Soporte prioritario', 'Capacitación para tu equipo'],
        };
    }
}
