<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaRequest;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\ActivityLogService;
use App\Services\ComprobanteService;
use App\Services\EmpresaService;
use App\Services\SaleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ventaController extends Controller
{
    protected EmpresaService $empresaService;
    protected SaleService $saleService;

    function __construct(EmpresaService $empresaService, SaleService $saleService)
    {
        $this->middleware('permission:ver-venta|crear-venta|mostrar-venta|eliminar-venta', ['only' => ['index']]);
        $this->middleware('permission:crear-venta', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-venta', ['only' => ['show']]);
        //$this->middleware('permission:eliminar-venta', ['only' => ['destroy']]);
        $this->middleware('check-caja-aperturada-user', ['only' => ['create', 'store']]);
        $this->middleware('check-show-venta-user', ['only' => ['show']]);
        $this->empresaService = $empresaService;
        $this->saleService = $saleService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Venta::with(['comprobante', 'cliente.persona', 'user'])
            ->where('user_id', Auth::id())
            ->latest('fecha_hora');

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_hora', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_hora', '<=', $request->fecha_hasta);
        }

        if ($request->filled('metodo_pago')) {
            $query->where('metodo_pago', $request->metodo_pago);
        }

        $ventas = $query->get();
        $empresa = $this->empresaService->obtenerEmpresa();
        $resumenVentas = [
            'operaciones' => $ventas->count(),
            'total' => round((float) $ventas->sum('total'), 2),
            'promedio' => round((float) $ventas->avg('total'), 2),
        ];
        $optionsMetodoPago = $this->empresaService->obtenerMetodosPagoHabilitados();

        return view('venta.index', compact('ventas', 'empresa', 'resumenVentas', 'optionsMetodoPago'));
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
        $optionsMetodoPago = $this->empresaService->obtenerMetodosPagoHabilitados();
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
        try {
            $venta = $this->saleService->create($request->user(), $request->validated());
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
            throw $e;
        } catch (Throwable $e) {
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
