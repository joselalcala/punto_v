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
            'moneda_id' => 1,
        ]);
    }
}
