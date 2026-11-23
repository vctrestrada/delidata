<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurante_id')->constrained('restaurantes')->cascadeOnDelete();
            $table->string('plan', 20);
            $table->unsignedSmallInteger('max_sucursales');
            $table->decimal('precio', 10, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            // activa | suspendida | cancelada (los demás estados se calculan con las fechas)
            $table->string('estado', 20)->default('activa');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['restaurante_id', 'fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencias');
    }
};
