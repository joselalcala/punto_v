<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class homeController extends Controller
{
    public function index(): View
    {
        if (!Auth::check()) {
            return view('welcome');
        }

        $empresa = Empresa::with('moneda')->first();
        $stockMinimo = $empresa?->stock_minimo_alerta ?? 5;

        $metricas = [
            'clientes' => Cliente::count(),
            'compras' => Compra::count(),
            'productos' => Producto::count(),
            'usuarios' => User::count(),
            'ventas_hoy' => (float) Venta::whereDate('fecha_hora', Carbon::today())->sum('total'),
            'compras_hoy' => (float) Compra::whereDate('fecha_hora', Carbon::today())->sum('total'),
        ];

        $totalVentasPorDia = DB::table('ventas')
            ->selectRaw('DATE(fecha_hora) as fecha, SUM(total) as total')
            ->where('fecha_hora', '>=', Carbon::now()->subDays(7))
            ->groupBy(DB::raw('DATE(fecha_hora)'))
            ->orderBy('fecha', 'asc')
            ->get()->toArray();

        $productosStockBajo = DB::table('productos')
            ->join('inventario', 'productos.id', '=', 'inventario.producto_id')
            ->where('inventario.cantidad', '>', 0)
            ->where('inventario.cantidad', '<=', $stockMinimo)
            ->orderBy('inventario.cantidad', 'asc')
            ->select('productos.nombre', 'productos.codigo', 'inventario.cantidad')
            ->limit(5)
            ->get();

        $topProductosVendidos = DB::table('producto_venta')
            ->join('productos', 'productos.id', '=', 'producto_venta.producto_id')
            ->join('ventas', 'ventas.id', '=', 'producto_venta.venta_id')
            ->where('ventas.fecha_hora', '>=', Carbon::now()->subDays(30))
            ->selectRaw('productos.nombre, SUM(producto_venta.cantidad) as total_vendido')
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('total_vendido')
            ->limit(5)
            ->get();

        $ultimasVentas = Venta::with(['cliente.persona', 'comprobante'])
            ->latest('fecha_hora')
            ->limit(5)
            ->get();

        $resumenMetodoPago = Venta::query()
            ->selectRaw('metodo_pago, COUNT(*) as operaciones, SUM(total) as total')
            ->where('fecha_hora', '>=', Carbon::now()->subDays(30))
            ->groupBy('metodo_pago')
            ->orderByDesc('total')
            ->get();

        return view('panel.index', compact(
            'empresa',
            'metricas',
            'totalVentasPorDia',
            'productosStockBajo',
            'topProductosVendidos',
            'ultimasVentas',
            'resumenMetodoPago',
            'stockMinimo'
        ));
    }
}
