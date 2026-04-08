<?php

namespace Tests\Feature;

use App\Models\Caja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MovimientoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_movements_only_allow_retiro_as_tipo(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        $this->actingAs($user);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $caja = Caja::create(['saldo_inicial' => 100]);

        $response = $this->post(route('movimientos.store'), [
            'descripcion' => 'Intento invalido',
            'monto' => 20,
            'metodo_pago' => 'EFECTIVO',
            'caja_id' => $caja->id,
            'tipo' => 'VENTA',
        ]);

        $response->assertSessionHasErrors('tipo');
        $this->assertDatabaseCount('movimientos', 0);
    }

    public function test_valid_retiro_is_saved_with_the_expected_tipo(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        $this->actingAs($user);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $caja = Caja::create(['saldo_inicial' => 100]);

        $response = $this->post(route('movimientos.store'), [
            'descripcion' => 'Retiro de caja',
            'monto' => 20,
            'metodo_pago' => 'EFECTIVO',
            'caja_id' => $caja->id,
            'tipo' => 'RETIRO',
        ]);

        $response->assertRedirect(route('movimientos.index', ['caja_id' => $caja->id]));
        $this->assertDatabaseHas('movimientos', [
            'descripcion' => 'Retiro de caja',
            'tipo' => 'RETIRO',
            'caja_id' => $caja->id,
        ]);
    }
}
