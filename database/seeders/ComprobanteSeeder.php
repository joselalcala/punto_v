<?php

namespace Database\Seeders;

use App\Models\Comprobante;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComprobanteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Comprobante::insert([
            [
                'nombre' => 'Boleta',
                'codigo' => 'BOLETA',
                'prefijo' => 'B',
                'descripcion' => 'Comprobante simplificado para venta mostrador',
                'longitud_numero' => 7,
                'activo' => true,
            ],
            [
                'nombre' => 'Factura',
                'codigo' => 'FACTURA',
                'prefijo' => 'F',
                'descripcion' => 'Comprobante fiscal para cliente con datos fiscales',
                'longitud_numero' => 7,
                'activo' => true,
            ]
        ]);
    }
}
