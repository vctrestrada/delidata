<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Un usuario de prueba por rol, con el correo ya verificado.
 * Contraseña de todos: password
 */
class UsuariosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Victor (Super Admin)', 'email' => 'superadmin@delidata.test', 'rol' => Rol::SuperAdmin],
            ['name' => 'Dueño Demo', 'email' => 'dueno@delidata.test', 'rol' => Rol::Dueno],
            ['name' => 'Encargado Demo', 'email' => 'encargado@delidata.test', 'rol' => Rol::Encargado],
            ['name' => 'Vendedor Demo', 'email' => 'vendedor@delidata.test', 'rol' => Rol::Vendedor],
        ];

        foreach ($usuarios as $datos) {
            $usuario = User::where('email', $datos['email'])->first()
                ?? User::factory()->create([
                    'name' => $datos['name'],
                    'email' => $datos['email'],
                    'password' => 'password',
                ]);

            $usuario->syncRoles([$datos['rol']->value]);
        }
    }
}
