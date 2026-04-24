<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = Carbon::now();
        $seccion = DB::table('entrevista_secciones')->where('slug', 'experiencia_laboral')->first();

        if ($seccion) {
            // Desplazar el orden de las preguntas existentes +1
            DB::table('entrevista_preguntas')
                ->where('seccion_id', $seccion->id)
                ->increment('orden');

            // Insertar la nueva pregunta
            DB::table('entrevista_preguntas')->insert([
                'seccion_id'     => $seccion->id,
                'enunciado'      => '¿Ha trabajado anteriormente?',
                'tipo_respuesta' => 'si_no',
                'opciones'       => json_encode(['Sí', 'No']),
                'obligatoria'    => true,
                'orden'          => 1,
                'clave_word'     => 'lab_ha_trabajado',
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $seccion = DB::table('entrevista_secciones')->where('slug', 'experiencia_laboral')->first();
        if ($seccion) {
            // Eliminar la pregunta
            DB::table('entrevista_preguntas')
                ->where('clave_word', 'lab_ha_trabajado')
                ->delete();

            // Revertir el orden
            DB::table('entrevista_preguntas')
                ->where('seccion_id', $seccion->id)
                ->decrement('orden');
        }
    }
};
