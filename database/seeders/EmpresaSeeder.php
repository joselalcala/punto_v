<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Empresa::insert([
            'nombre' => 'Comercial Base Demo',
            'propietario' => 'Administrador General',
            'ruc' => 'RFC000000000',
            'porcentaje_impuesto' => '16',
            'abreviatura_impuesto' => 'IVA',
            'direccion' => 'Calle Principal 100, Ciudad Demo',
            'stock_minimo_alerta' => 5,
            'modo_impuesto_incluido' => false,
            'metodos_pago_habilitados' => json_encode(['EFECTIVO', 'TARJETA', 'TRANSFERENCIA']),
            'moneda_id' => 1,
        ]);
    }
}
