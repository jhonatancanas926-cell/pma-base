<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Importacion extends Model
{
    protected $table = 'importaciones';

    protected $fillable = [
        'user_id', 'nombre_archivo', 'tipo', 'estado',
        'filas_total', 'filas_exitosas', 'filas_con_error',
        'errores', 'estadisticas',
    ];

    protected function casts(): array
    {
        return [
            'errores'      => 'array',
            'estadisticas' => 'array',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
