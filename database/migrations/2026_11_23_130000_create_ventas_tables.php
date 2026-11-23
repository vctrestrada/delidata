<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ventas del punto de venta.
 * El detalle guarda una "foto" del nombre, precio y costo del producto
 * al momento de vender, para que los reportes no cambien si después
 * se edita o elimina el producto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            // Folio consecutivo por restaurante (1, 2, 3…).
            $table->unsignedInteger('folio');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('metodo_pago', 20);
            $table->decimal('recibido', 12, 2)->nullable();
            $table->decimal('cambio', 12, 2)->nullable();
            $table->string('estado', 20)->default('completada');
            $table->string('notas', 255)->nullable();
            $table->foreignId('cancelada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelada_at')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();
            $table->timestamps();

            $table->unique(['restaurante_id', 'folio']);
            $table->index(['negocio_id', 'created_at']);
            $table->index(['restaurante_id', 'created_at']);
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->string('nombre', 120);
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->decimal('importe', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_detalles');
        Schema::dropIfExists('ventas');
    }
};
