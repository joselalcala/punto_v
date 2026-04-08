<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompraRequest;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedore;
use App\Services\ActivityLogService;
use App\Services\ComprobanteService;
use App\Services\EmpresaService;
use App\Services\PurchaseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class compraController extends Controller
{
    protected EmpresaService $empresaService;
    protected PurchaseService $purchaseService;

    function __construct(EmpresaService $empresaService, PurchaseService $purchaseService)
    {
        $this->middleware('permission:ver-compra|crear-compra|mostrar-compra|eliminar-compra', ['only' => ['index']]);
        $this->middleware('permission:crear-compra', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-compra', ['only' => ['show']]);
        //$this->middleware('permission:eliminar-compra', ['only' => ['destroy']]);
        $this->middleware('check-show-compra-user', ['only' => ['show']]);
        $this->empresaService = $empresaService;
        $this->purchaseService = $purchaseService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $compras = Compra::with('comprobante', 'proveedore.persona')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('compra.index', compact('compras'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(ComprobanteService $comprobanteService): View
    {
        $proveedores = Proveedore::whereHas('persona', function ($query) {
            $query->where('estado', 1);
        })->get();
        $comprobantes = $comprobanteService->obtenerComprobantes();
        $productos = Producto::where('estado', 1)->get();
        $optionsMetodoPago = $this->empresaService->obtenerMetodosPagoHabilitados();
        $empresa = $this->empresaService->obtenerEmpresa();

        return view('compra.create', compact(
            'proveedores',
            'comprobantes',
            'productos',
            'optionsMetodoPago',
            'empresa'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompraRequest $request): RedirectResponse
    {
        try {
            $compra = $this->purchaseService->create(
                $request->user(),
                $request->validated(),
                $request->file('file_comprobante')
            );

            ActivityLogService::log('Creación de compra', 'Compras', [
                'compra_id' => $compra->id,
                'proveedore_id' => $compra->proveedore_id,
                'subtotal' => $compra->subtotal,
                'impuesto' => $compra->impuesto,
                'total' => $compra->total,
            ]);
            return redirect()->route('compras.index')->with('success', 'compra exitosa');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Error al crear la compra', ['error' => $e->getMessage()]);
            return redirect()->route('compras.index')->with('error', 'Ups, algo falló');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Compra $compra): View
    {
        $empresa = $this->empresaService->obtenerEmpresa();
        return view('compra.show', compact('compra', 'empresa'));
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
        /*
        Compra::where('id', $id)
            ->update([
                'estado' => 0
            ]);

        return redirect()->route('compras.index')->with('success', 'Compra eliminada');*/
    }
}
