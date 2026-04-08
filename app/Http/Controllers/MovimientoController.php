<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPagoEnum;
use App\Enums\TipoMovimientoEnum;
use App\Http\Requests\StoreMovimientoRequest;
use App\Models\Caja;
use App\Models\Movimiento;
use App\Services\ActivityLogService;
use App\Services\EmpresaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MovimientoController extends Controller
{
    public function __construct(private readonly EmpresaService $empresaService)
    {
        $this->middleware('check_movimiento_caja_user', ['only' => ['index', 'create', 'store']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $caja = Caja::with('movimientos')->findOrfail($request->caja_id);
        $resumen = [
            'ventas' => round((float) $caja->movimientos->where('tipo', TipoMovimientoEnum::Venta->value)->sum('monto'), 2),
            'retiros' => round((float) $caja->movimientos->where('tipo', TipoMovimientoEnum::Retiro->value)->sum('monto'), 2),
            'neto' => round((float) $caja->movimientos->where('tipo', TipoMovimientoEnum::Venta->value)->sum('monto') - (float) $caja->movimientos->where('tipo', TipoMovimientoEnum::Retiro->value)->sum('monto'), 2),
        ];

        return view('movimiento.index', compact('caja', 'resumen'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $caja_id = $request->get('caja_id');
        $optionsMetodoPago = $this->empresaService->obtenerMetodosPagoHabilitados();
        return view('movimiento.create', compact('optionsMetodoPago', 'caja_id'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMovimientoRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['tipo'] = TipoMovimientoEnum::Retiro->value;

            Movimiento::create($data);
            ActivityLogService::log('Creación de movimiento', 'Movimientos', $data);
            return redirect()->route('movimientos.index', ['caja_id' => $request->caja_id])
                ->with('success', 'retiro registrado');
        } catch (Throwable $e) {
            Log::error('Error al crear el movimiento', ['error' => $e->getMessage()]);
            return redirect()->route('movimientos.index', ['caja_id' => $request->caja_id])
                ->with('error', 'Ups, algo falló');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }
}
