<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empresa extends Model
{
    protected $guarded = ['id'];

    protected $table = 'empresa';

    protected $casts = [
        'modo_impuesto_incluido' => 'boolean',
        'metodos_pago_habilitados' => 'array',
    ];

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }

    public function getMetodosPagoConfiguradosAttribute(): array
    {
        return $this->metodos_pago_habilitados ?: [];
    }
}
