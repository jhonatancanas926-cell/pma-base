<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'test_id',
        'nombre',
        'codigo',
        'descripcion',
        'tiempo_limite',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'tiempo_limite' => 'integer',
            'orden'         => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class)->orderBy('orden');
    }

    public function resultados(): HasMany
    {
        return $this->hasMany(Resultado::class);
    }
}
