<?php

namespace App\Services;

use App\Enums\MetodoPagoEnum;
use App\Models\Empresa;
use Illuminate\Support\Facades\Cache;

class EmpresaService
{
    /**
     * Obtener del cache el registro de la empresa
     */
    public function obtenerEmpresa(): Empresa
    {
        return Cache::remember('empresa', 3600, function () {
            return Empresa::first();
        });
    }

    /**
     * Limpiar la Cache de empresa
     */
    public function limpiarCacheEmpresa(): void
    {
        Cache::forget('empresa');
    }

    /**
     * Obtener solo los metodos de pago habilitados en la configuracion.
     *
     * @return array<int, MetodoPagoEnum>
     */
    public function obtenerMetodosPagoHabilitados(): array
    {
        $empresa = $this->obtenerEmpresa();
        $metodosConfigurados = $empresa->metodos_pago_configurados;

        if ($metodosConfigurados === []) {
            return MetodoPagoEnum::cases();
        }

        return array_values(array_filter(
            MetodoPagoEnum::cases(),
            fn (MetodoPagoEnum $metodo): bool => in_array($metodo->value, $metodosConfigurados, true)
        ));
    }
}
