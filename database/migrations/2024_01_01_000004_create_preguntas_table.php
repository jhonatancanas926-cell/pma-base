<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->integer('numero')->comment('Número de pregunta dentro de la categoría');
            $table->text('enunciado')->comment('Texto principal de la pregunta');
            $table->enum('tipo', [
                'opcion_multiple',  // FACTOR V: sinónimo, FACTOR R: completar serie
                'verdadero_falso',  // FACTOR N: ¿Es correcto el resultado?
                'texto',            // Respuesta abierta
            ])->default('opcion_multiple');
            $table->json('metadatos')->nullable()->comment('Datos extra: sumandos, serie, etc.');
            $table->string('respuesta_correcta')->nullable()->comment('Valor de la opción correcta o true/false');
            $table->integer('puntaje')->default(1);
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['categoria_id', 'numero']);
            $table->index(['categoria_id', 'orden']);
            $table->index('tipo');
        });

        Schema::create('opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained('preguntas')->cascadeOnDelete();
            $table->string('letra')->comment('A, B, C, D');
            $table->text('texto');
            $table->boolean('es_correcta')->default(false);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->index(['pregunta_id', 'orden']);
            $table->index(['pregunta_id', 'es_correcta']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones');
        Schema::dropIfExists('preguntas');
    }
};
