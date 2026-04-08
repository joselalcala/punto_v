<?php

namespace Tests\Feature;

use App\Models\Caracteristica;
use App\Models\Categoria;
use App\Models\Compra;
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
use App\Models\Proveedore;
use App\Models\Ubicacione;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CompraControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Proveedore $proveedor;
    protected Comprobante $comprobante;
    protected Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'crear-compra', 'guard_name' => 'web']);

        $role = Role::create([
            'name' => 'tester-compra',
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(
            Permission::query()
                ->where('name', 'crear-compra')
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

        Documento::insert(['nombre' => 'RFC']);
        Comprobante::insert(['nombre' => 'Factura']);

        $documento = Documento::firstOrFail();
        $this->comprobante = Comprobante::firstOrFail();

        $persona = Persona::create([
            'razon_social' => 'Proveedor Demo',
            'direccion' => 'Direccion demo',
            'telefono' => '5555555555',
            'tipo' => 'JURIDICA',
            'email' => 'proveedor@example.com',
            'estado' => 1,
            'documento_id' => $documento->id,
            'numero_documento' => 'RFC123456',
        ]);

        $this->proveedor = Proveedore::create(['persona_id' => $persona->id]);
        $this->producto = $this->createProductoConInventario(5, 10);
    }

    public function test_store_recalculates_purchase_totals_and_updates_inventory_and_kardex(): void
    {
        $response = $this->post(route('compras.store'), [
            'proveedore_id' => $this->proveedor->id,
            'comprobante_id' => $this->comprobante->id,
            'numero_comprobante' => 'FAC-001',
            'metodo_pago' => 'EFECTIVO',
            'fecha_hora' => '2026-04-08T10:30',
            'subtotal' => 1,
            'impuesto' => 3.20,
            'total' => 1,
            'arrayidproducto' => [$this->producto->id],
            'arraycantidad' => [4],
            'arraypreciocompra' => [8],
            'arrayfechavencimiento' => [null],
        ]);

        $compra = Compra::first();

        $response->assertRedirect(route('compras.index'));
        $this->assertNotNull($compra);
        $this->assertEquals(32.00, (float) $compra->subtotal);
        $this->assertEquals(3.20, (float) $compra->impuesto);
        $this->assertEquals(35.20, (float) $compra->total);
        $this->assertDatabaseHas('compra_producto', [
            'compra_id' => $compra->id,
            'producto_id' => $this->producto->id,
            'cantidad' => 4,
            'precio_compra' => 8,
        ]);
        $this->assertDatabaseHas('inventario', [
            'producto_id' => $this->producto->id,
            'cantidad' => 9,
        ]);
        $this->assertDatabaseHas('kardex', [
            'producto_id' => $this->producto->id,
            'tipo_transaccion' => 'COMPRA',
            'saldo' => 9,
            'costo_unitario' => 8,
        ]);
        $this->assertDatabaseHas('productos', [
            'id' => $this->producto->id,
            'precio' => 9.6,
            'estado' => 1,
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
            'codigo' => 'P0002',
            'nombre' => 'Producto Compra Demo',
            'descripcion' => 'Producto para pruebas de compra',
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
