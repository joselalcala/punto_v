<?php

namespace App\Services;

use App\Enums\TipoMovimientoEnum;
use App\Jobs\EnviarComprobanteVentaJob;
use App\Models\Caja;
use App\Models\Comprobante;
use App\Models\Empresa;
use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private readonly InventoryTransactionService $inventoryTransactionService) {}

    public function create(User $user, array $validated): Venta
    {
        return DB::transaction(function () use ($user, $validated) {
            $caja = Caja::query()
                ->where('user_id', $user->id)
                ->where('estado', 1)
                ->lockForUpdate()
                ->first();

            if (!$caja) {
                throw ValidationException::withMessages([
                    'caja' => 'Debe aperturar una caja.',
                ]);
            }

            $empresa = Empresa::query()->firstOrFail();
            $arrayProducto_id = $validated['arrayidproducto'];
            $arrayCantidad = $validated['arraycantidad'];

            if (count($arrayProducto_id) !== count($arrayCantidad)) {
                throw ValidationException::withMessages([
                    'arraycantidad' => 'El detalle de la venta es inconsistente.',
                ]);
            }

            $productos = Producto::with('inventario')
                ->whereIn('id', $arrayProducto_id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($productos->count() !== count(array_unique($arrayProducto_id))) {
                throw ValidationException::withMessages([
                    'arrayidproducto' => 'Uno o más productos no son válidos.',
                ]);
            }

            $detalleVenta = [];
            $subtotal = 0.0;

            foreach ($arrayProducto_id as $index => $productoId) {
                $producto = $productos->get((int) $productoId);
                $cantidad = (int) $arrayCantidad[$index];

                if (!$producto || !$producto->inventario) {
                    throw ValidationException::withMessages([
                        'arrayidproducto' => 'Uno o más productos no tienen inventario inicializado.',
                    ]);
                }

                if ($producto->estado != 1) {
                    throw ValidationException::withMessages([
                        'arrayidproducto' => 'Solo puede vender productos activos.',
                    ]);
                }

                if ($producto->inventario->cantidad < $cantidad) {
                    throw ValidationException::withMessages([
                        'arraycantidad' => "Stock insuficiente para {$producto->nombre}.",
                    ]);
                }

                $precioVenta = round((float) $producto->precio, 2);
                $subtotal += round($cantidad * $precioVenta, 2);
                $detalleVenta[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_venta' => $precioVenta,
                ];
            }

            $subtotal = round($subtotal, 2);
            $impuesto = round($subtotal * ((float) $empresa->porcentaje_impuesto / 100), 2);
            $total = round($subtotal + $impuesto, 2);
            $montoRecibido = round((float) $validated['monto_recibido'], 2);

            if ($montoRecibido < $total) {
                throw ValidationException::withMessages([
                    'monto_recibido' => 'El monto recibido debe cubrir el total de la venta.',
                ]);
            }

            $tipoComprobante = Comprobante::query()->findOrFail($validated['comprobante_id'])->nombre;

            $venta = Venta::create([
                'cliente_id' => $validated['cliente_id'],
                'user_id' => $user->id,
                'caja_id' => $caja->id,
                'comprobante_id' => $validated['comprobante_id'],
                'numero_comprobante' => $this->generateSaleNumber($caja->id, $tipoComprobante),
                'metodo_pago' => $validated['metodo_pago'],
                'fecha_hora' => Carbon::now()->toDateTimeString(),
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
                'monto_recibido' => $montoRecibido,
                'vuelto_entregado' => round($montoRecibido - $total, 2),
            ]);

            foreach ($detalleVenta as $detalle) {
                $venta->productos()->syncWithoutDetaching([
                    $detalle['producto']->id => [
                        'cantidad' => $detalle['cantidad'],
                        'precio_venta' => $detalle['precio_venta'],
                    ],
                ]);

                $this->inventoryTransactionService->registerSaleDetail(
                    $detalle['producto'],
                    $detalle['cantidad'],
                    $venta->id
                );
            }

            Movimiento::create([
                'tipo' => TipoMovimientoEnum::Venta,
                'descripcion' => 'Venta n° ' . $venta->numero_comprobante,
                'monto' => $venta->total,
                'metodo_pago' => $venta->metodo_pago,
                'caja_id' => $caja->id,
            ]);

            EnviarComprobanteVentaJob::dispatch($venta->id)->afterCommit();

            return $venta;
        });
    }

    private function generateSaleNumber(int $cajaId, string $tipoComprobante): string
    {
        $prefijo = strtoupper(substr($tipoComprobante, 0, 1));

        $ultimaVenta = Venta::query()
            ->where('caja_id', $cajaId)
            ->lockForUpdate()
            ->latest('id')
            ->first();

        $ultimoNumero = $ultimaVenta
            ? (int) substr($ultimaVenta->numero_comprobante, 7)
            : 0;

        return sprintf('%s%03d - %07d', $prefijo, $cajaId, $ultimoNumero + 1);
    }
}
