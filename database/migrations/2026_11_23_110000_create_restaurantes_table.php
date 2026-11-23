<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restaurantes (locales) que el dueño registra dentro de su negocio.
 * El plan de la suscripción limita cuántos puede tener.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('negocio_id')->constrained('negocios')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->time('abre')->nullable();
            $table->time('cierra')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['negocio_id', 'nombre']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Restaurante donde trabaja (solo encargados y vendedores).
            $table->foreignId('restaurante_id')->nullable()->after('negocio_id')->constrained('restaurantes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restaurante_id');
        });

        Schema::dropIfExists('restaurantes');
    }
};
