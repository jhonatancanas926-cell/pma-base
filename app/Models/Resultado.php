<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resultado extends Model
{
    protected $table = 'resultados';

    protected $fillable = [
        'sesion_id', 'categoria_id', 'total_preguntas',
        'respondidas', 'correctas', 'incorrectas', 'omitidas',
        'puntaje_bruto', 'puntaje_percentil', 'nivel', 'analisis_errores',
    ];

    protected function casts(): array
    {
        return [
            'analisis_errores'  => 'array',
            'puntaje_bruto'     => 'float',
            'puntaje_percentil' => 'float',
        ];
    }

    public function sesion(): BelongsTo    { return $this->belongsTo(SesionPrueba::class, 'sesion_id'); }
    public function categoria(): BelongsTo { return $this->belongsTo(Categoria::class); }

    public function porcentajeAcierto(): float
    {
        if ($this->total_preguntas === 0) return 0.0;
        return round(($this->correctas / $this->total_preguntas) * 100, 2);
    }
}
