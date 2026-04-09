<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespuestaUsuario extends Model
{
    protected $table = 'respuestas_usuario';

    protected $fillable = [
        'sesion_id', 'pregunta_id', 'respuesta_dada',
        'opcion_id', 'es_correcta', 'tiempo_respuesta',
        'intentos', 'respondida_en',
    ];

    protected function casts(): array
    {
        return [
            'es_correcta'     => 'boolean',
            'respondida_en'   => 'datetime',
            'tiempo_respuesta'=> 'integer',
            'intentos'        => 'integer',
        ];
    }

    public function sesion(): BelongsTo   { return $this->belongsTo(SesionPrueba::class, 'sesion_id'); }
    public function pregunta(): BelongsTo { return $this->belongsTo(Pregunta::class); }
    public function opcion(): BelongsTo   { return $this->belongsTo(Opcion::class); }
}
