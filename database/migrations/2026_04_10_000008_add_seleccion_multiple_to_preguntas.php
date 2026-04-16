<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Agregar 'seleccion_multiple' al enum tipo de preguntas
        DB::statement("ALTER TABLE preguntas MODIFY COLUMN tipo
            ENUM('opcion_multiple','verdadero_falso','texto','seleccion_multiple')
            NOT NULL DEFAULT 'opcion_multiple'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE preguntas MODIFY COLUMN tipo
            ENUM('opcion_multiple','verdadero_falso','texto')
            NOT NULL DEFAULT 'opcion_multiple'");
    }
};