<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private readonly InventoryTransactionService $inventoryTransactionService) {}

    public function create(User $user, array $validated, ?UploadedFile $comprobanteFile = null): Compra
    {
        return DB::transaction(function () use ($user, $validated, $comprobanteFile) {
            $arrayProducto_id = $validated['arrayidproducto'];
            $arrayCantidad = $validated['arraycantidad'];
            $arrayPrecioCompra = $validated['arraypreciocompra'];
            $arrayFechaVencimiento = $validated['arrayfechavencimiento'];

            if (
                count($arrayProducto_id) !== count($arrayCantidad) ||
                count($arrayProducto_id) !== count($arrayPrecioCompra) ||
                count($arrayProducto_id) !== count($arrayFechaVencimiento)
            ) {
                throw ValidationException::withMessages([
                    'arrayidproducto' => 'El detalle de la compra es inconsistente.',
                ]);
            }

            $productos = Producto::query()
                ->whereIn('id', $arrayProducto_id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($productos->count() !== count(array_unique($arrayProducto_id))) {
                throw ValidationException::withMessages([
                    'arrayidproducto' => 'Uno o más productos no son válidos.',
                ]);
            }

            $detalleCompra = [];
            $subtotal = 0.0;

            foreach ($arrayProducto_id as $index => $productoId) {
                $producto = $productos->get((int) $productoId);
                $cantidad = (int) $arrayCantidad[$index];
                $precioCompra = round((float) $arrayPrecioCompra[$index], 2);
                $fechaVencimiento = $arrayFechaVencimiento[$index] ?: null;

                if (!$producto) {
                    throw ValidationException::withMessages([
                        'arrayidproducto' => 'Uno o más productos no son válidos.',
                    ]);
                }

                $subtotal += round($cantidad * $precioCompra, 2);
                $detalleCompra[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_compra' => $precioCompra,
                    'fecha_vencimiento' => $fechaVencimiento,
                ];
            }

            $subtotal = round($subtotal, 2);
            $impuesto = round((float) $validated['impuesto'], 2);
            $total = round($subtotal + $impuesto, 2);

            $compra = new Compra();
            $compraPath = $comprobanteFile ? $compra->handleUploadFile($comprobanteFile) : null;

            $compra = Compra::create([
                'proveedore_id' => $validated['proveedore_id'],
                'user_id' => $user->id,
                'comprobante_id' => $validated['comprobante_id'],
                'numero_comprobante' => $validated['numero_comprobante'] ?? null,
                'comprobante_path' => $compraPath,
                'metodo_pago' => $validated['metodo_pago'],
                'fecha_hora' => $validated['fecha_hora'],
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
            ]);

            foreach ($detalleCompra as $detalle) {
                $compra->productos()->syncWithoutDetaching([
                    $detalle['producto']->id => [
                        'cantidad' => $detalle['cantidad'],
                        'precio_compra' => $detalle['precio_compra'],
                        'fecha_vencimiento' => $detalle['fecha_vencimiento'],
                    ],
                ]);

                $this->inventoryTransactionService->registerPurchaseDetail(
                    $detalle['producto'],
                    $detalle['cantidad'],
                    $detalle['precio_compra'],
                    $detalle['fecha_vencimiento'],
                    $compra->id
                );
            }

            return $compra;
        });
    }
}
