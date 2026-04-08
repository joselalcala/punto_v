<?php

namespace App\Services;

use App\Models\Comprobante;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ComprobanteService
{
    /**
     * Obtener del cache Todos los registros de Comprobantes
     */
    public function obtenerComprobantes(): Collection
    {
        return Cache::rememberForever('comprobantes', function () {
            return Comprobante::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();
        });
    }

    /**
     * Limpiar la Cache de comprobantes
     */
    public function limpiarCacheComprobante(): void
    {
        Cache::forget('comprobantes');
    }
}
