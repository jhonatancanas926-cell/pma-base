<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entrevista extends Model
{
    protected $fillable = [
        'user_id',
        'estado',
        'completado_por',
        'completada_en',
        'pma_habilitado',
    ];

    protected $casts = [
        'completada_en' => 'datetime',
        'pma_habilitado' => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completado_por');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(EntrevistaRespuesta::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function estaCompleta(): bool
    {
        return $this->estado === 'completada';
    }

    /**
     * Devuelve las respuestas como array clave_word => valor,
     * útil para poblar el reporte Word.
     */
    public function respuestasIndexadas(): array
    {
        return $this->respuestas()
            ->with('pregunta')
            ->get()
            ->filter(fn($r) => $r->pregunta?->clave_word)
            ->mapWithKeys(fn($r) => [$r->pregunta->clave_word => $r->respuesta])
            ->toArray();
    }
}
