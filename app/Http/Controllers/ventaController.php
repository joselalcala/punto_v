<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPagoEnum;
use App\Events\CreateVentaDetalleEvent;
use App\Events\CreateVentaEvent;
use App\Http\Requests\StoreVentaRequest;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\ActivityLogService;
use App\Services\ComprobanteService;
use App\Services\EmpresaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ventaController extends Controller
{
    protected EmpresaService $empresaService;

    function __construct(EmpresaService $empresaService)
    {
        $this->middleware('permission:ver-venta|crear-venta|mostrar-venta|eliminar-venta', ['only' => ['index']]);
        $this->middleware('permission:crear-venta', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-venta', ['only' => ['show']]);
        //$this->middleware('permission:eliminar-venta', ['only' => ['destroy']]);
        $this->middleware('check-caja-aperturada-user', ['only' => ['create', 'store']]);
        $this->middleware('check-show-venta-user', ['only' => ['show']]);
        $this->empresaService = $empresaService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $ventas = Venta::with(['comprobante', 'cliente.persona', 'user'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('venta.index', compact('ventas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ComprobanteService $comprobanteService): View
    {

        $productos = Producto::join('inventario as i', function ($join) {
            $join->on('i.producto_id', '=', 'productos.id');
        })
            ->join('presentaciones as p', function ($join) {
                $join->on('p.id', '=', 'productos.presentacione_id');
            })
            ->select(
                'p.sigla',
                'productos.nombre',
                'productos.codigo',
                'productos.id',
                'i.cantidad',
                'productos.precio'
            )
            ->where('productos.estado', 1)
            ->where('i.cantidad', '>', 0)
            ->get();

        $clientes = Cliente::whereHas('persona', function ($query) {
            $query->where('estado', 1);
        })->get();
        $comprobantes = $comprobanteService->obtenerComprobantes();
        $optionsMetodoPago = MetodoPagoEnum::cases();
        $empresa = $this->empresaService->obtenerEmpresa();

        return view('venta.create', compact(
            'productos',
            'clientes',
            'comprobantes',
            'optionsMetodoPago',
            'empresa'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVentaRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $validated = $request->validated();
            $arrayProducto_id = $validated['arrayidproducto'];
            $arrayCantidad = $validated['arraycantidad'];
            $empresa = Empresa::query()->firstOrFail();

            if (count($arrayProducto_id) !== count($arrayCantidad)) {
                throw ValidationException::withMessages([
                    'arraycantidad' => 'El detalle de la venta es inconsistente.',
                ]);
            }

            $productos = Producto::with('inventario')
                ->whereIn('id', $arrayProducto_id)
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
                    'producto_id' => $producto->id,
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

            $venta = Venta::create([
                'cliente_id' => $validated['cliente_id'],
                'comprobante_id' => $validated['comprobante_id'],
                'metodo_pago' => $validated['metodo_pago'],
                'subtotal' => $subtotal,
                'impuesto' => $impuesto,
                'total' => $total,
                'monto_recibido' => $montoRecibido,
                'vuelto_entregado' => round($montoRecibido - $total, 2),
            ]);

            foreach ($detalleVenta as $detalle) {
                $venta->productos()->syncWithoutDetaching([
                    $detalle['producto_id'] => [
                        'cantidad' => $detalle['cantidad'],
                        'precio_venta' => $detalle['precio_venta'],
                    ]
                ]);

                CreateVentaDetalleEvent::dispatch(
                    $venta,
                    $detalle['producto_id'],
                    $detalle['cantidad'],
                    $detalle['precio_venta']
                );
            }

            CreateVentaEvent::dispatch($venta);

            DB::commit();
            ActivityLogService::log('Creación de una venta', 'Ventas', [
                'venta_id' => $venta->id,
                'cliente_id' => $venta->cliente_id,
                'subtotal' => $venta->subtotal,
                'impuesto' => $venta->impuesto,
                'total' => $venta->total,
            ]);
            return redirect()->route('movimientos.index', ['caja_id' => $venta->caja_id])
                ->with('success', 'Venta registrada');
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear la venta', ['error' => $e->getMessage()]);
            return redirect()->route('ventas.index')->with('error', 'Ups, algo falló');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Venta $venta): View
    {
        $empresa =  $this->empresaService->obtenerEmpresa();
        return view('venta.show', compact('venta', 'empresa'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        /* Venta::where('id', $id)
            ->update([
                'estado' => 0
            ]);

        return redirect()->route('ventas.index')->with('success', 'Venta eliminada');*/
    }
}
