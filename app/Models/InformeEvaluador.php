<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformeEvaluador extends Model
{
    protected $table = 'informe_evaluador';

    protected $fillable = [
        'sesion_id', 'evaluador_id',
        'concepto_toxicologico',
        // Condiciones PMA-R auto
        'cond_procesos_cognitivos_desc',    'cond_procesos_cognitivos_dev',
        'cond_tolerancia_estres_desc',      'cond_tolerancia_estres_dev',
        'cond_evaluacion_riesgos_desc',     'cond_evaluacion_riesgos_dev',
        'cond_proyeccion_tcp_desc',         'cond_proyeccion_tcp_dev',
        'cond_seguridad_personal_desc',     'cond_seguridad_personal_dev',
        'cond_relaciones_interpersonales_desc', 'cond_relaciones_interpersonales_dev',
        // Condiciones manuales (NEO PI-R futuro)
        'cond_manejo_conflictos_desc',      'cond_manejo_conflictos_dev',
        'cond_seguimiento_normas_desc',     'cond_seguimiento_normas_dev',
        'cond_manejo_presiones_desc',       'cond_manejo_presiones_dev',
        'cond_reaccion_emergencias_desc',   'cond_reaccion_emergencias_dev',
        'cond_afiliacion_social_desc',      'cond_afiliacion_social_dev',
        // Cierre
        'conclusiones', 'concepto_global', 'recomendaciones', 'estado',
    ];

    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionPrueba::class, 'sesion_id');
    }

    public function evaluador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluador_id');
    }

    public function estaListo(): bool
    {
        return $this->estado === 'listo';
    }
}
