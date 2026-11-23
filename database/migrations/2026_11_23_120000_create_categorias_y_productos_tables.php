<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo del negocio:
 *  - categorias: agrupan los productos en el menú (Tacos, Bebidas…).
 *  - productos: catálogo único del negocio con precio base y costo.
 *  - producto_restaurante: en qué restaurantes se vende cada producto,
 *    con precio especial opcional y si está disponible o agotado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['negocio_id', 'nombre']);
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('nombre', 120);
            $table->string('descripcion', 255)->nullable();
            $table->decimal('precio', 10, 2);
            // Lo que le cuesta al negocio preparar el producto (para calcular el margen).
            $table->decimal('costo', 10, 2)->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['negocio_id', 'nombre']);
        });

        Schema::create('producto_restaurante', function (Blueprint $table) {
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            // Si es null se usa el precio base del producto.
            $table->decimal('precio', 10, 2)->nullable();
            // El encargado lo apaga cuando el producto se agota.
            $table->boolean('disponible')->default(true);
            $table->timestamps();

            $table->primary(['producto_id', 'restaurante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_restaurante');
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
    }
};
