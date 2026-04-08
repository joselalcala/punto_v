<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Caja;
use App\Models\Comprobante;
use App\Models\Compra;
use App\Models\Documento;
use App\Models\Empresa;
use App\Models\Moneda;
use App\Models\Persona;
use App\Models\Proveedore;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard_with_metrics(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        $this->actingAs($user);
        $moneda = Moneda::create([
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
            'moneda_id' => $moneda->id,
            'stock_minimo_alerta' => 5,
            'modo_impuesto_incluido' => false,
            'metodos_pago_habilitados' => ['EFECTIVO', 'TARJETA'],
        ]);

        Documento::query()->insert(['nombre' => 'RFC']);
        $comprobante = Comprobante::create([
            'nombre' => 'Factura',
            'codigo' => 'FACTURA',
            'prefijo' => 'F',
            'descripcion' => 'Comprobante de prueba',
            'longitud_numero' => 7,
            'activo' => true,
        ]);

        $personaCliente = Persona::create([
            'razon_social' => 'Cliente Dashboard',
            'direccion' => 'Direccion demo',
            'telefono' => '5555555555',
            'tipo' => 'NATURAL',
            'email' => 'cliente@demo.test',
            'estado' => 1,
            'documento_id' => 1,
            'numero_documento' => 'CLI001',
        ]);
        $cliente = Cliente::create(['persona_id' => $personaCliente->id]);
        $caja = Caja::create(['saldo_inicial' => 100]);

        $personaProveedor = Persona::create([
            'razon_social' => 'Proveedor Dashboard',
            'direccion' => 'Direccion demo',
            'telefono' => '5555555556',
            'tipo' => 'JURIDICA',
            'email' => 'proveedor@demo.test',
            'estado' => 1,
            'documento_id' => 1,
            'numero_documento' => 'PRO001',
        ]);
        $proveedor = Proveedore::create(['persona_id' => $personaProveedor->id]);

        Venta::create([
            'cliente_id' => $cliente->id,
            'user_id' => $user->id,
            'caja_id' => $caja->id,
            'comprobante_id' => $comprobante->id,
            'numero_comprobante' => 'F001 - 0000001',
            'metodo_pago' => 'EFECTIVO',
            'fecha_hora' => now(),
            'subtotal' => 100,
            'impuesto' => 16,
            'total' => 116,
            'monto_recibido' => 120,
            'vuelto_entregado' => 4,
        ]);

        Compra::create([
            'proveedore_id' => $proveedor->id,
            'user_id' => $user->id,
            'comprobante_id' => $comprobante->id,
            'numero_comprobante' => 'FAC-001',
            'comprobante_path' => null,
            'metodo_pago' => 'TARJETA',
            'fecha_hora' => now(),
            'subtotal' => 80,
            'impuesto' => 0,
            'total' => 80,
        ]);

        $response = $this->get(route('panel'));

        $response->assertOk();
        $response->assertSee('Ventas de hoy');
        $response->assertSee('Clientes');
        $response->assertSee('Compras del día');
    }
}
