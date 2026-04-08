<?php

namespace App\Services;

use App\Enums\TipoTransaccionEnum;
use App\Models\Inventario;
use App\Models\Kardex;
use App\Models\Producto;
use Illuminate\Validation\ValidationException;

class InventoryTransactionService
{
    private const MARGEN_GANANCIA = 0.2;

    public function initializeProduct(array $data): Inventario
    {
        $inventario = Inventario::create($data);

        Kardex::create([
            'producto_id' => $data['producto_id'],
            'tipo_transaccion' => TipoTransaccionEnum::Apertura,
            'descripcion_transaccion' => 'Apertura del producto',
            'entrada' => $data['cantidad'],
            'salida' => null,
            'saldo' => $data['cantidad'],
            'costo_unitario' => $data['costo_unitario'],
        ]);

        $this->syncProductPricingAndState($inventario->producto_id, (float) $data['costo_unitario']);

        return $inventario;
    }

    public function registerPurchaseDetail(
        Producto $producto,
        int $cantidad,
        float $precioCompra,
        ?string $fechaVencimiento,
        int $compraId
    ): void {
        $inventario = Inventario::query()
            ->where('producto_id', $producto->id)
            ->lockForUpdate()
            ->first();

        if (!$inventario) {
            throw ValidationException::withMessages([
                'arrayidproducto' => "El producto {$producto->nombre} no tiene inventario inicializado.",
            ]);
        }

        $nuevoSaldo = $inventario->cantidad + $cantidad;

        Kardex::create([
            'producto_id' => $producto->id,
            'tipo_transaccion' => TipoTransaccionEnum::Compra,
            'descripcion_transaccion' => 'Entrada de producto por la compra n°' . $compraId,
            'entrada' => $cantidad,
            'salida' => null,
            'saldo' => $nuevoSaldo,
            'costo_unitario' => $precioCompra,
        ]);

        $inventario->update([
            'cantidad' => $nuevoSaldo,
            'fecha_vencimiento' => $fechaVencimiento,
        ]);

        $this->syncProductPricingAndState($producto->id, $precioCompra);
    }

    public function registerSaleDetail(Producto $producto, int $cantidad, int $ventaId): void
    {
        $inventario = Inventario::query()
            ->where('producto_id', $producto->id)
            ->lockForUpdate()
            ->first();

        if (!$inventario) {
            throw ValidationException::withMessages([
                'arrayidproducto' => "El producto {$producto->nombre} no tiene inventario inicializado.",
            ]);
        }

        if ($inventario->cantidad < $cantidad) {
            throw ValidationException::withMessages([
                'arraycantidad' => "Stock insuficiente para {$producto->nombre}.",
            ]);
        }

        $ultimoKardex = Kardex::query()
            ->where('producto_id', $producto->id)
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if (!$ultimoKardex) {
            throw ValidationException::withMessages([
                'arrayidproducto' => "El producto {$producto->nombre} no tiene kardex inicializado.",
            ]);
        }

        $nuevoSaldo = $inventario->cantidad - $cantidad;

        Kardex::create([
            'producto_id' => $producto->id,
            'tipo_transaccion' => TipoTransaccionEnum::Venta,
            'descripcion_transaccion' => 'Salida de producto por la venta n°' . $ventaId,
            'entrada' => null,
            'salida' => $cantidad,
            'saldo' => $nuevoSaldo,
            'costo_unitario' => $ultimoKardex->costo_unitario,
        ]);

        $inventario->update([
            'cantidad' => $nuevoSaldo,
        ]);

        $this->syncProductPricingAndState($producto->id, (float) $ultimoKardex->costo_unitario);
    }

    private function syncProductPricingAndState(int $productoId, float $costoUnitario): void
    {
        Producto::query()
            ->where('id', $productoId)
            ->update([
                'estado' => 1,
                'precio' => $this->calculateSalePrice($costoUnitario),
            ]);
    }

    private function calculateSalePrice(float $costoUnitario): float
    {
        return round($costoUnitario + ($costoUnitario * self::MARGEN_GANANCIA), 2);
    }
}
