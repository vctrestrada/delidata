<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gastos de cada restaurante (insumos, renta, nómina, servicios…).
 * El comprobante se guarda en el disco privado y solo se sirve a usuarios autorizados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->string('categoria', 20);
            $table->string('concepto', 150);
            $table->decimal('monto', 12, 2);
            $table->string('metodo_pago', 20);
            $table->string('proveedor', 120)->nullable();
            $table->string('comprobante')->nullable();
            $table->string('notas', 255)->nullable();
            $table->timestamps();

            $table->index(['negocio_id', 'fecha']);
            $table->index(['restaurante_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
