<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Negocio;
use App\Models\Producto;
use Illuminate\Database\Seeder;

/**
 * Menú de ejemplo para "La Esquina Taquería".
 * Plaza Neza cobra algunos productos más caros y tiene un producto agotado.
 */
class MenuDemoSeeder extends Seeder
{
    public function run(): void
    {
        $negocio = Negocio::where('nombre', 'La Esquina Taquería')->firstOrFail();
        $centro = $negocio->restaurantes()->where('nombre', 'La Esquina — Centro')->firstOrFail();
        $plaza = $negocio->restaurantes()->where('nombre', 'La Esquina — Plaza Neza')->firstOrFail();

        // [nombre, descripción, precio, costo, precio en plaza (null = igual), solo en centro]
        $menu = [
            'Tacos' => [
                ['Taco al pastor', 'Con piña, cebolla y cilantro', 18, 7.5, 22, false],
                ['Taco de suadero', 'Suadero dorado en su jugo', 18, 8, 22, false],
                ['Taco de bistec', 'Bistec de res a la plancha', 20, 9.5, 24, false],
                ['Taco campechano', 'Bistec con longaniza', 22, 10, 26, false],
                ['Orden de 5 al pastor', 'Cinco tacos al pastor con salsa de la casa', 80, 35, 95, false],
            ],
            'Especialidades' => [
                ['Gringa', 'Tortilla de harina con queso y pastor', 45, 19, 52, false],
                ['Volcán de bistec', 'Tostada con queso gratinado y bistec', 38, 16, null, true],
                ['Quesadilla de queso', 'Tortilla de maíz hecha a mano', 30, 11, 35, false],
            ],
            'Bebidas' => [
                ['Agua de horchata', 'Vaso de 1 litro', 30, 8, null, false],
                ['Agua de jamaica', 'Vaso de 1 litro', 30, 7, null, false],
                ['Refresco de 600 ml', 'Varios sabores', 25, 14, 28, false],
            ],
            'Postres' => [
                ['Flan napolitano', 'Rebanada con cajeta', 35, 12, 40, false],
                ['Arroz con leche', 'Con canela', 28, 8, null, true],
            ],
        ];

        $orden = 1;

        foreach ($menu as $nombreCategoria => $productos) {
            $categoria = Categoria::updateOrCreate(
                ['negocio_id' => $negocio->id, 'nombre' => $nombreCategoria],
                ['orden' => $orden++],
            );

            foreach ($productos as [$nombre, $descripcion, $precio, $costo, $precioPlaza, $soloCentro]) {
                $producto = Producto::updateOrCreate(
                    ['negocio_id' => $negocio->id, 'nombre' => $nombre],
                    ['categoria_id' => $categoria->id, 'descripcion' => $descripcion, 'precio' => $precio, 'costo' => $costo, 'activo' => true],
                );

                $restaurantes = [$centro->id => ['precio' => null, 'disponible' => true]];

                if (! $soloCentro) {
                    $restaurantes[$plaza->id] = [
                        'precio' => $precioPlaza,
                        // En Plaza Neza ya se acabó el flan.
                        'disponible' => $nombre !== 'Flan napolitano',
                    ];
                }

                $producto->restaurantes()->sync($restaurantes);
            }
        }
    }
}
