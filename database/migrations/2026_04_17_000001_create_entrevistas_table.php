<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── Secciones del formulario de entrevista ─────────────────────────
        Schema::create('entrevista_secciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique()->comment('Ej: datos_personales, antecedentes_medicos');
            $table->string('tipo')->default('formulario')
                ->comment('formulario=texto libre, escala=opciones Likert');
            $table->integer('orden')->default(0);
            $table->text('descripcion')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        // ── Preguntas / campos de cada sección ────────────────────────────
        Schema::create('entrevista_preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('entrevista_secciones')->cascadeOnDelete();
            $table->string('enunciado');
            $table->string('tipo_respuesta')
                ->comment('texto|textarea|fecha|si_no|escala_3|seleccion|numero');
            $table->json('opciones')->nullable()->comment('Para tipo escala_3 o seleccion');
            $table->boolean('obligatoria')->default(true);
            $table->integer('orden')->default(0);
            $table->string('clave_word')->nullable()
                ->comment('Clave para mapear al campo del Word, ej: nombre_completo');
            $table->timestamps();

            $table->index(['seccion_id', 'orden']);
        });

        // ── Instancia de entrevista por aspirante ─────────────────────────
        Schema::create('entrevistas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('estado', ['pendiente', 'en_progreso', 'completada'])->default('pendiente');
            $table->foreignId('completado_por')->nullable()->constrained('users')->nullOnDelete()
                ->comment('NULL = el propio aspirante; ID = evaluador que la marcó completa');
            $table->timestamp('completada_en')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index('estado');
        });

        // ── Respuestas individuales ───────────────────────────────────────
        Schema::create('entrevista_respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrevista_id')->constrained('entrevistas')->cascadeOnDelete();
            $table->foreignId('pregunta_id')->constrained('entrevista_preguntas')->cascadeOnDelete();
            $table->text('respuesta')->nullable();
            $table->foreignId('editado_por')->nullable()->constrained('users')->nullOnDelete()
                ->comment('NULL = respuesta original del aspirante');
            $table->timestamp('editada_en')->nullable();
            $table->timestamps();

            $table->unique(['entrevista_id', 'pregunta_id']);
            $table->index('entrevista_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrevista_respuestas');
        Schema::dropIfExists('entrevistas');
        Schema::dropIfExists('entrevista_preguntas');
        Schema::dropIfExists('entrevista_secciones');
    }
};
