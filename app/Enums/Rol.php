<?php

namespace App\Enums;

/**
 * Roles del sistema Delidata.
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
            self::Encargado => 'Encargado de sucursal',
            self::Vendedor => 'Vendedor',
        };
    }

    /** Ruta a la que se envía al usuario después de iniciar sesión. */
    public function rutaInicio(): string
    {
        return match ($this) {
            self::SuperAdmin => 'admin.inicio',
            self::Dueno => 'negocio.inicio',
            self::Encargado => 'sucursal.inicio',
            self::Vendedor => 'ventas.inicio',
        };
    }

    /** Patrón de nombres de ruta del panel de este rol (para el menú). */
    public function patronRutas(): string
    {
        return explode('.', $this->rutaInicio())[0].'.*';
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
                'licencias.gestionar',
                'restaurantes.gestionar',
                'usuarios.gestionar',
            ],
            self::Dueno => [
                'sucursales.gestionar',
                'usuarios.gestionar',
                'productos.gestionar',
                'ventas.ver',
                'gastos.registrar',
                'reportes.ver',
            ],
            self::Encargado => [
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

    /** @return list<string> */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
