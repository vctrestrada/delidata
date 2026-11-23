<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Negocio al que pertenece (dueño, encargado o vendedor). El super admin no tiene.
            $table->foreignId('negocio_id')->nullable()->after('id')->constrained('negocios')->nullOnDelete();
            $table->boolean('activo')->default(true)->after('password');
            // Claves de los recorridos guiados que el usuario ya vio.
            $table->json('recorridos_vistos')->nullable()->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('negocio_id');
            $table->dropColumn(['activo', 'recorridos_vistos']);
        });
    }
};
