<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Moneda extends Model
{
    protected $fillable = ['estandar_iso', 'nombre_completo', 'simbolo'];

    public function empresa(): HasOne
    {
        return $this->hasOne(Empresa::class);
    }
}
