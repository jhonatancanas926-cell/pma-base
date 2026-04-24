<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EntrevistaSeccion extends Model
{
    protected $table = 'entrevista_secciones';

    protected $fillable = [
        'nombre', 'slug', 'tipo', 'orden', 'descripcion', 'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function preguntas(): HasMany
    {
        return $this->hasMany(EntrevistaPregunta::class, 'seccion_id')->orderBy('orden');
    }
}
