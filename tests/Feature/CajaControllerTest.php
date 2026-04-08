<?php

namespace Tests\Feature;

use App\Models\Ubicacione;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CajaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_a_caja_with_selected_ubicacion(): void
    {
        $user = User::factory()->create(['estado' => 1]);
        $ubicacion = Ubicacione::create(['nombre' => 'Sucursal Centro']);

        $response = $this->actingAs($user)->post(route('cajas.store'), [
            'saldo_inicial' => 150,
            'ubicacione_id' => $ubicacion->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cajas', [
            'saldo_inicial' => 150,
            'ubicacione_id' => $ubicacion->id,
            'user_id' => $user->id,
        ]);
    }
}
