<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrevistaRespuesta extends Model
{
    protected $table = 'entrevista_respuestas';

    protected $fillable = [
        'entrevista_id', 'pregunta_id', 'respuesta', 'editado_por', 'editada_en',
    ];

    protected $casts = [
        'editada_en' => 'datetime',
    ];

    public function entrevista(): BelongsTo
    {
        return $this->belongsTo(Entrevista::class);
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(EntrevistaPregunta::class, 'pregunta_id');
    }

    public function editadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editado_por');
    }
}
