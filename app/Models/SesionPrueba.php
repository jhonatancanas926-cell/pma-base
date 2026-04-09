<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesionPrueba extends Model
{
    protected $table = 'sesiones_prueba';

    protected $fillable = [
        'user_id', 'test_id', 'estado', 'iniciada_en',
        'finalizada_en', 'tiempo_total', 'ip_cliente',
        'agente_usuario', 'metadatos',
    ];

    protected function casts(): array
    {
        return [
            'iniciada_en'   => 'datetime',
            'finalizada_en' => 'datetime',
            'metadatos'     => 'array',
            'tiempo_total'  => 'integer',
        ];
    }

    public function user(): BelongsTo      { return $this->belongsTo(User::class); }
    public function test(): BelongsTo      { return $this->belongsTo(Test::class); }
    public function respuestas(): HasMany  { return $this->hasMany(RespuestaUsuario::class, 'sesion_id'); }
    public function resultados(): HasMany  { return $this->hasMany(Resultado::class, 'sesion_id'); }

    public function estaActiva(): bool     { return $this->estado === 'en_progreso'; }
    public function estaCompletada(): bool { return $this->estado === 'completada'; }
}
