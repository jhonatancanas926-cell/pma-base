<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrevistaPregunta extends Model
{
    protected $table = 'entrevista_preguntas';

    protected $fillable = [
        'seccion_id', 'enunciado', 'tipo_respuesta',
        'opciones', 'obligatoria', 'orden', 'clave_word',
    ];

    protected $casts = [
        'opciones'    => 'array',
        'obligatoria' => 'boolean',
    ];

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(EntrevistaSeccion::class, 'seccion_id');
    }
}
