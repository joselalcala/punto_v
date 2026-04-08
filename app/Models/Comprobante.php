<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobante extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
