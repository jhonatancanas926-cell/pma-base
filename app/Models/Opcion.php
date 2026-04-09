<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opcion extends Model
{
    protected $table = 'opciones';

    protected $fillable = [
        'pregunta_id',
        'letra',
        'texto',
        'es_correcta',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'es_correcta' => 'boolean',
            'orden'       => 'integer',
        ];
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }
}
