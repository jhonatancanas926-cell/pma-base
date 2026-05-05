<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeguimientoAlumno extends Model
{
    protected $table = 'seguimiento_alumno';

    protected $fillable = [
        'sesion_id', 'evaluador_id',
        'concepto_inicial',
        'observaciones_profesional',
        'evolucion_seguimiento',
        'reporte_tutor',
        'acompanamiento_academico',
        'acompanamiento_psicologico',
        'resultados_seguimiento',
        'recomendaciones_finales',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionPrueba::class, 'sesion_id');
    }

    public function evaluador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluador_id');
    }
}
