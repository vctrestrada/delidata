<?php

namespace App\Enums;

/**
 * Roles del sistema Delidata.
 *
 * Jerarquía:
 *  - Super admin: administra las suscripciones de los negocios (nada más).
 *  - Dueño: administrador de su negocio; registra sus restaurantes y a su personal.
 *  - Encargado: responsable de un restaurante; administra a sus vendedores.
 *  - Vendedor: registra ventas en su restaurante.
 *
 * El orden de los casos define la jerarquía: si un usuario tuviera
 * más de un rol, se toma como principal el primero que aparezca aquí.
 */
enum Rol: string
{
    case SuperAdmin = 'super-admin';
    case Dueno = 'dueno';
    case Encargado = 'encargado';
    case Vendedor = 'vendedor';

    /** Nombre legible para mostrar en la interfaz. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrador',
            self::Dueno => 'Dueño',
            self::Encargado => 'Encargado',
            self::Vendedor => 'Vendedor',
        };
    }

    /** Ruta a la que se envía al usuario después de iniciar sesión. */
    public function rutaInicio(): string
    {
        return match ($this) {
            self::SuperAdmin => 'admin.inicio',
            self::Dueno => 'negocio.inicio',
            self::Encargado => 'encargado.inicio',
            self::Vendedor => 'ventas.inicio',
        };
    }

    /** ¿Trabaja dentro de un restaurante específico? */
    public function trabajaEnRestaurante(): bool
    {
        return in_array($this, [self::Encargado, self::Vendedor], true);
    }

    /**
     * Permisos que tiene cada rol.
     *
     * @return list<string>
     */
    public function permisos(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                'suscripciones.gestionar',
            ],
            self::Dueno => [
                'restaurantes.gestionar',
                'personal.gestionar',
                'productos.gestionar',
                'ventas.ver',
                'gastos.registrar',
                'reportes.ver',
            ],
            self::Encargado => [
                'personal.gestionar',
                'productos.gestionar',
                'ventas.ver',
                'gastos.registrar',
                'reportes.ver',
            ],
            self::Vendedor => [
                'ventas.registrar',
            ],
        };
    }

    /**
     * Roles que puede dar de alta cada rol.
     *
     * @return list<self>
     */
    public function puedeCrear(): array
    {
        return match ($this) {
            self::SuperAdmin => [self::Dueno],
            self::Dueno => [self::Encargado, self::Vendedor],
            self::Encargado => [self::Vendedor],
            self::Vendedor => [],
        };
    }

    /** @return list<string> */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
