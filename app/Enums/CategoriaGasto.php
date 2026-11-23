<?php

namespace App\Enums;

enum CategoriaGasto: string
{
    case Insumos = 'insumos';
    case Renta = 'renta';
    case Nomina = 'nomina';
    case Servicios = 'servicios';
    case Mantenimiento = 'mantenimiento';
    case Otros = 'otros';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Insumos => 'Insumos',
            self::Renta => 'Renta',
            self::Nomina => 'Nómina',
            self::Servicios => 'Servicios',
            self::Mantenimiento => 'Mantenimiento',
            self::Otros => 'Otros',
        };
    }

    public function descripcion(): string
    {
        return match ($this) {
            self::Insumos => 'Carne, tortillas, verdura, refrescos, desechables…',
            self::Renta => 'Renta del local',
            self::Nomina => 'Sueldos y pagos al personal',
            self::Servicios => 'Luz, agua, gas, internet, teléfono',
            self::Mantenimiento => 'Reparaciones, limpieza, equipo',
            self::Otros => 'Cualquier otro gasto',
        };
    }

    public function icono(): string
    {
        return match ($this) {
            self::Insumos => 'shopping-bag',
            self::Renta => 'home-modern',
            self::Nomina => 'user-group',
            self::Servicios => 'bolt',
            self::Mantenimiento => 'wrench-screwdriver',
            self::Otros => 'ellipsis-horizontal-circle',
        };
    }

    /** Tono de naranja/gris para las barras del resumen (de más a menos intenso). */
    public function colorBarra(): string
    {
        return match ($this) {
            self::Insumos => 'bg-orange-500',
            self::Renta => 'bg-orange-400',
            self::Nomina => 'bg-orange-300',
            self::Servicios => 'bg-amber-300',
            self::Mantenimiento => 'bg-zinc-400',
            self::Otros => 'bg-zinc-300',
        };
    }
}
