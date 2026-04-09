<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Baterías de pruebas (e.g., PMA, NEO PI-R)
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('codigo')->unique()->comment('Ej: PMA-R, NEO-PI-R');
            $table->text('descripcion')->nullable();
            $table->string('version')->default('1.0');
            $table->integer('tiempo_limite')->nullable()->comment('Minutos totales. NULL = sin límite');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('activo');
        });

        // Categorías / Factores dentro de una prueba
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('codigo')->comment('Ej: FACTOR_V, FACTOR_N');
            $table->text('descripcion')->nullable();
            $table->integer('tiempo_limite')->nullable()->comment('Minutos por factor');
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->unique(['test_id', 'codigo']);
            $table->index(['test_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
        Schema::dropIfExists('tests');
    }
};
