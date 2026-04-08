<?php

namespace App\Http\Controllers;

use App\Enums\MetodoPagoEnum;
use App\Http\Requests\UpdateEmpresaRequest;
use App\Models\Empresa;
use App\Models\Moneda;
use App\Services\ActivityLogService;
use App\Services\EmpresaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $empresa = Empresa::first();
        $monedas = Moneda::all();
        $metodosPago = MetodoPagoEnum::cases();

        return view('empresa.index', compact('empresa', 'monedas', 'metodosPago'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function update(UpdateEmpresaRequest $request, Empresa $empresa, EmpresaService $empresaService): RedirectResponse
    {
        try {
            $data = $request->validated();
            $data['modo_impuesto_incluido'] = $request->boolean('modo_impuesto_incluido');
            $empresa->update($data);
            $empresaService->limpiarCacheEmpresa();
            ActivityLogService::log('Edición de empresa', 'Empresa', $data);
            return redirect()->route('empresa.index')->with('success', 'Empresa editada');
        } catch (Throwable $e) {
            Log::error('Error al editar la empresa', ['error' => $e->getMessage()]);
            return redirect()->route('empresa.index')->with('error', 'Ups, algo falló');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
