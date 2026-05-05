<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluadorPerfil extends Model
{
    protected $table = 'evaluador_perfil';

    protected $fillable = [
        'user_id',
        'nombre_completo',
        'tarjeta_profesional',
        'firma_path',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** URL pública de la firma para incrustar en PDF/preview */
    public function firmaUrl(): ?string
    {
        return $this->firma_path
            ? asset('storage/' . $this->firma_path)
            : null;
    }
}
