<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Licencias (periodos de suscripción) de cada negocio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('plan', 20);
            $table->unsignedSmallInteger('max_restaurantes');
            $table->decimal('precio', 10, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            // activa | suspendida | cancelada (los demás estados se calculan con las fechas)
            $table->string('estado', 20)->default('activa');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['negocio_id', 'fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencias');
    }
};
