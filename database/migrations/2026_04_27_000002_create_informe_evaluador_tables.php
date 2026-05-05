<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Perfil del evaluador (firma + datos profesionales) ────────────
        Schema::create('evaluador_perfil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nombre_completo')->nullable();
            $table->string('tarjeta_profesional')->nullable();
            $table->string('firma_path')->nullable()->comment('Ruta de la imagen de firma escaneada');
            $table->timestamps();
        });

        // ── Campos del evaluador por sesión PMA-R ─────────────────────────
        Schema::create('informe_evaluador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->unique()->constrained('sesiones_prueba')->cascadeOnDelete();
            $table->foreignId('evaluador_id')->constrained('users')->cascadeOnDelete();

            // Toxicológico
            $table->text('concepto_toxicologico')->nullable();

            // 6 condiciones auto-llenadas desde PMA-R (descripcion auto + desarrollo editable)
            $table->text('cond_procesos_cognitivos_desc')->nullable();
            $table->text('cond_procesos_cognitivos_dev')->nullable();
            $table->text('cond_tolerancia_estres_desc')->nullable();
            $table->text('cond_tolerancia_estres_dev')->nullable();
            $table->text('cond_evaluacion_riesgos_desc')->nullable();
            $table->text('cond_evaluacion_riesgos_dev')->nullable();
            $table->text('cond_proyeccion_tcp_desc')->nullable();
            $table->text('cond_proyeccion_tcp_dev')->nullable();
            $table->text('cond_seguridad_personal_desc')->nullable();
            $table->text('cond_seguridad_personal_dev')->nullable();
            $table->text('cond_relaciones_interpersonales_desc')->nullable();
            $table->text('cond_relaciones_interpersonales_dev')->nullable();

            // 5 condiciones manuales (NEO PI-R futuro)
            $table->text('cond_manejo_conflictos_desc')->nullable();
            $table->text('cond_manejo_conflictos_dev')->nullable();
            $table->text('cond_seguimiento_normas_desc')->nullable();
            $table->text('cond_seguimiento_normas_dev')->nullable();
            $table->text('cond_manejo_presiones_desc')->nullable();
            $table->text('cond_manejo_presiones_dev')->nullable();
            $table->text('cond_reaccion_emergencias_desc')->nullable();
            $table->text('cond_reaccion_emergencias_dev')->nullable();
            $table->text('cond_afiliacion_social_desc')->nullable();
            $table->text('cond_afiliacion_social_dev')->nullable();

            // Secciones finales
            $table->text('conclusiones')->nullable();
            $table->enum('concepto_global', [
                'Admitido',
                'Admitido con seguimiento',
                'No admitido',
            ])->nullable();
            $table->text('recomendaciones')->nullable();

            $table->enum('estado', ['borrador', 'listo'])->default('borrador');
            $table->timestamps();
        });

        // ── Seguimiento del alumno (se llena al final del curso) ──────────
        Schema::create('seguimiento_alumno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sesion_id')->unique()->constrained('sesiones_prueba')->cascadeOnDelete();
            $table->foreignId('evaluador_id')->constrained('users')->cascadeOnDelete();

            $table->text('concepto_inicial')->nullable();
            $table->text('observaciones_profesional')->nullable();
            $table->text('evolucion_seguimiento')->nullable();
            $table->text('reporte_tutor')->nullable();
            $table->text('acompanamiento_academico')->nullable();
            $table->text('acompanamiento_psicologico')->nullable();
            $table->text('resultados_seguimiento')->nullable();
            $table->text('recomendaciones_finales')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_alumno');
        Schema::dropIfExists('informe_evaluador');
        Schema::dropIfExists('evaluador_perfil');
    }
};
