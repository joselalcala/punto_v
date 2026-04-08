<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\Caracteristica;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\Inventario;
use App\Models\Kardex;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Persona;
use App\Models\Presentacione;
use App\Models\Producto;
use App\Models\Ubicacione;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class VentaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Caja $caja;
    protected Cliente $cliente;
    protected Comprobante $comprobante;
    protected Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'crear-venta', 'guard_name' => 'web']);

        $role = Role::create([
            'name' => 'tester-venta',
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(
            Permission::query()
                ->where('name', 'crear-venta')
                ->get()
        );

        $this->user = User::factory()->create(['estado' => 1]);
        $this->user->assignRole($role);
        $this->actingAs($this->user);

        Moneda::insert([
            'estandar_iso' => 'MXN',
            'nombre_completo' => 'Peso mexicano',
            'simbolo' => '$',
        ]);

        Empresa::create([
            'nombre' => 'Empresa Demo',
            'propietario' => 'Admin',
            'ruc' => 'RFC123456789',
            'porcentaje_impuesto' => 16,
            'abreviatura_impuesto' => 'IVA',
            'direccion' => 'Direccion demo',
            'moneda_id' => 1,
        ]);

        Documento::insert(['nombre' => 'DNI']);
        Comprobante::insert([
            'nombre' => 'Boleta',
            'codigo' => 'BOLETA',
            'prefijo' => 'BOL',
            'descripcion' => 'Comprobante de prueba',
            'longitud_numero' => 5,
            'activo' => true,
        ]);

        $documento = Documento::firstOrFail();
        $this->comprobante = Comprobante::firstOrFail();

        $persona = Persona::create([
            'razon_social' => 'Cliente Demo',
            'direccion' => 'Direccion demo',
            'telefono' => '5555555555',
            'tipo' => 'NATURAL',
            'email' => 'cliente@example.com',
            'estado' => 1,
            'documento_id' => $documento->id,
            'numero_documento' => 'ABC123',
        ]);

        $this->cliente = Cliente::create(['persona_id' => $persona->id]);
        $this->caja = Caja::create(['saldo_inicial' => 100]);
        $this->producto = $this->createProductoConInventario(5, 10);
    }

    public function test_store_recalculates_sale_totals_and_uses_the_server_price(): void
    {
        $response = $this->post(route('ventas.store'), [
            'cliente_id' => $this->cliente->id,
            'comprobante_id' => $this->comprobante->id,
            'metodo_pago' => 'EFECTIVO',
            'arrayidproducto' => [$this->producto->id],
            'arraycantidad' => [2],
            'arrayprecioventa' => [1],
            'subtotal' => 1,
            'impuesto' => 0,
            'total' => 1,
            'monto_recibido' => 30,
            'vuelto_entregado' => 0,
        ]);

        $venta = Venta::first();
        $precioServidor = round((float) $this->producto->precio, 2);
        $subtotalEsperado = round($precioServidor * 2, 2);
        $porcentajeImpuesto = (float) Empresa::firstOrFail()->porcentaje_impuesto;
        $impuestoEsperado = round($subtotalEsperado * ($porcentajeImpuesto / 100), 2);
        $totalEsperado = round($subtotalEsperado + $impuestoEsperado, 2);
        $vueltoEsperado = round(30.00 - $totalEsperado, 2);

        $response->assertRedirect(route('movimientos.index', ['caja_id' => $this->caja->id]));
        $this->assertNotNull($venta);
        $this->assertEquals('BOL001 - 00001', $venta->numero_comprobante);
        $this->assertEquals($subtotalEsperado, (float) $venta->subtotal);
        $this->assertEquals($impuestoEsperado, (float) $venta->impuesto);
        $this->assertEquals($totalEsperado, (float) $venta->total);
        $this->assertEquals($vueltoEsperado, (float) $venta->vuelto_entregado);
        $this->assertDatabaseHas('producto_venta', [
            'venta_id' => $venta->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            'precio_venta' => $precioServidor,
        ]);
        $this->assertDatabaseHas('inventario', [
            'producto_id' => $this->producto->id,
            'cantidad' => 3,
        ]);
        $this->assertDatabaseHas('movimientos', [
            'caja_id' => $this->caja->id,
            'tipo' => 'VENTA',
        ]);
    }

    public function test_store_rejects_sales_with_insufficient_stock(): void
    {
        $response = $this->from(route('ventas.create'))->post(route('ventas.store'), [
            'cliente_id' => $this->cliente->id,
            'comprobante_id' => $this->comprobante->id,
            'metodo_pago' => 'EFECTIVO',
            'arrayidproducto' => [$this->producto->id],
            'arraycantidad' => [6],
            'arrayprecioventa' => [12],
            'subtotal' => 72,
            'impuesto' => 11.52,
            'total' => 83.52,
            'monto_recibido' => 90,
            'vuelto_entregado' => 6.48,
        ]);

        $response->assertRedirect(route('ventas.create'));
        $response->assertSessionHasErrors('arraycantidad');
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('movimientos', 0);
        $this->assertDatabaseHas('inventario', [
            'producto_id' => $this->producto->id,
            'cantidad' => 5,
        ]);
    }

    protected function createProductoConInventario(int $cantidad, float $costoUnitario): Producto
    {
        $categoria = Categoria::create([
            'caracteristica_id' => Caracteristica::create(['nombre' => 'Categoria Demo'])->id,
        ]);

        $marca = Marca::create([
            'caracteristica_id' => Caracteristica::create(['nombre' => 'Marca Demo'])->id,
        ]);

        $presentacion = Presentacione::create([
            'caracteristica_id' => Caracteristica::create(['nombre' => 'Presentacion Demo'])->id,
            'sigla' => 'PZ',
        ]);

        $producto = Producto::create([
            'codigo' => 'P0001',
            'nombre' => 'Producto Demo',
            'descripcion' => 'Producto para pruebas',
            'estado' => 1,
            'precio' => $costoUnitario,
            'marca_id' => $marca->id,
            'presentacione_id' => $presentacion->id,
            'categoria_id' => $categoria->id,
        ]);

        Kardex::create([
            'producto_id' => $producto->id,
            'tipo_transaccion' => 'APERTURA',
            'descripcion_transaccion' => 'Apertura del producto',
            'entrada' => $cantidad,
            'salida' => null,
            'saldo' => $cantidad,
            'costo_unitario' => $costoUnitario,
        ]);

        Inventario::create([
            'producto_id' => $producto->id,
            'ubicacione_id' => Ubicacione::create(['nombre' => 'Principal'])->id,
            'cantidad' => $cantidad,
        ]);

        return $producto->fresh(['inventario']);
    }
}
