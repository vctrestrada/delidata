<?php

namespace Database\Seeders;

use App\Enums\Rol;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea los 4 roles de Delidata y les asigna sus permisos.
 * Se puede ejecutar varias veces sin duplicar datos.
 */
class RolesYPermisosSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia la caché de permisos de Spatie antes de modificarlos.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = collect(Rol::cases())
            ->flatMap(fn (Rol $rol) => $rol->permisos())
            ->unique();

        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso, 'web');
        }

        foreach (Rol::cases() as $rol) {
            Role::findOrCreate($rol->value, 'web')->syncPermissions($rol->permisos());
        }
    }
}
