<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Moneda;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpresaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_stores_operational_configuration_values(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        $moneda = Moneda::create([
            'estandar_iso' => 'MXN',
            'nombre_completo' => 'Peso mexicano',
            'simbolo' => '$',
        ]);

        $empresa = Empresa::create([
            'nombre' => 'Empresa Demo',
            'propietario' => 'Admin',
            'ruc' => 'RFC123456789',
            'porcentaje_impuesto' => 16,
            'abreviatura_impuesto' => 'IVA',
            'direccion' => 'Direccion demo',
            'correo' => 'demo@example.com',
            'telefono' => '5555555555',
            'ubicacion' => 'Centro',
            'moneda_id' => $moneda->id,
            'stock_minimo_alerta' => 5,
            'modo_impuesto_incluido' => false,
            'metodos_pago_habilitados' => ['EFECTIVO', 'TARJETA'],
        ]);

        $response = $this->actingAs($user)->put(route('empresa.update', ['empresa' => $empresa]), [
            'nombre' => 'Empresa Configurada',
            'propietario' => 'Nuevo responsable',
            'ruc' => 'RFC999999999',
            'porcentaje_impuesto' => 8,
            'abreviatura_impuesto' => 'IVA',
            'direccion' => 'Nueva direccion',
            'correo' => 'nuevo@example.com',
            'telefono' => '5551234567',
            'ubicacion' => 'Sucursal Norte',
            'moneda_id' => $moneda->id,
            'stock_minimo_alerta' => 3,
            'modo_impuesto_incluido' => '1',
            'metodos_pago_habilitados' => ['EFECTIVO', 'TRANSFERENCIA'],
        ]);

        $response->assertRedirect(route('empresa.index'));
        $this->assertDatabaseHas('empresa', [
            'id' => $empresa->id,
            'nombre' => 'Empresa Configurada',
            'stock_minimo_alerta' => 3,
            'modo_impuesto_incluido' => 1,
        ]);
        $this->assertEquals(
            ['EFECTIVO', 'TRANSFERENCIA'],
            $empresa->fresh()->metodos_pago_habilitados
        );
    }
}
