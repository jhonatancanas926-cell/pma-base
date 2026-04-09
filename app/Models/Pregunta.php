<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pregunta extends Model
{
    use SoftDeletes;

    protected $table = 'preguntas';

    protected $fillable = [
        'categoria_id',
        'numero',
        'enunciado',
        'tipo',
        'metadatos',
        'respuesta_correcta',
        'puntaje',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'metadatos' => 'array',
            'activo'    => 'boolean',
            'puntaje'   => 'integer',
            'orden'     => 'integer',
            'numero'    => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(Opcion::class)->orderBy('orden');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaUsuario::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function opcionCorrecta(): ?Opcion
    {
        return $this->opciones()->where('es_correcta', true)->first();
    }

    public function verificarRespuesta(string $respuesta): bool
    {
        return match ($this->tipo) {
            'opcion_multiple' => $this->respuesta_correcta === $respuesta,
            'verdadero_falso' => strtolower($this->respuesta_correcta) === strtolower($respuesta),
            default           => false,
        };
    }
}
