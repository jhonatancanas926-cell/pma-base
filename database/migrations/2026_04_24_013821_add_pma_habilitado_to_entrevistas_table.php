<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('entrevistas', function (Blueprint $table) {
            $table->boolean('pma_habilitado')->default(false)->after('estado');
        });

        // Eliminar redundancias "(si aplica)" de la base de datos
        DB::statement("UPDATE entrevista_preguntas SET enunciado = REPLACE(enunciado, ' (si aplica)', '')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entrevistas', function (Blueprint $table) {
            $table->dropColumn('pma_habilitado');
        });
    }
};
