<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tests';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'version',
        'tiempo_limite',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo'        => 'boolean',
            'tiempo_limite' => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────

    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class)->orderBy('orden');
    }

    public function preguntas(): HasManyThrough
    {
        return $this->hasManyThrough(Pregunta::class, Categoria::class);
    }

    public function sesiones(): HasMany
    {
        return $this->hasMany(SesionPrueba::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    public function totalPreguntas(): int
    {
        return $this->preguntas()->count();
    }
}
