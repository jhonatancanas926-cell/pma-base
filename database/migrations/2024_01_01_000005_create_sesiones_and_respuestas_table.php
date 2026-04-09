<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sesión = instancia de un usuario tomando una prueba
        Schema::create('sesiones_prueba', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('tests')->cascadeOnDelete();
            $table->enum('estado', ['en_progreso', 'completada', 'abandonada', 'expirada'])
                  ->default('en_progreso');
            $table->timestamp('iniciada_en')->useCurrent();
            $table->timestamp('finalizada_en')->nullable();
            $table->integer('tiempo_total')->nullable()->comment('Segundos empleados');
            $table->string('ip_cliente', 45)->nullable();
            $table->string('agente_usuario')->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'test_id']);
            $table->index(['user_id', 'estado']);
            $table->index('estado');
        });

        // Respuestas individuales
        Schema::create('respuestas_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones_prueba')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('preguntas')->cascadeOnDelete();
            $table->string('respuesta_dada')->nullable()->comment('Valor elegido o texto');
            $table->foreignId('opcion_id')->nullable()->constrained('opciones')->nullOnDelete();
            $table->boolean('es_correcta')->nullable();
            $table->integer('tiempo_respuesta')->nullable()->comment('Segundos en responder');
            $table->integer('intentos')->default(1);
            $table->timestamp('respondida_en')->useCurrent();
            $table->timestamps();

            $table->unique(['sesion_id', 'pregunta_id']);
            $table->index(['sesion_id', 'es_correcta']);
            $table->index('pregunta_id');
        });

        // Resultados calculados por categoría
        Schema::create('resultados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->constrained('sesiones_prueba')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->integer('total_preguntas');
            $table->integer('respondidas');
            $table->integer('correctas');
            $table->integer('incorrectas');
            $table->integer('omitidas');
            $table->decimal('puntaje_bruto', 8, 2)->default(0);
            $table->decimal('puntaje_percentil', 5, 2)->nullable();
            $table->string('nivel')->nullable()->comment('Bajo, Medio, Alto, Muy Alto');
            $table->json('analisis_errores')->nullable()->comment('Patrones de error detectados');
            $table->timestamps();

            $table->unique(['sesion_id', 'categoria_id']);
            $table->index('categoria_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados');
        Schema::dropIfExists('respuestas_usuario');
        Schema::dropIfExists('sesiones_prueba');
    }
};
