<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->string('codigo', 20)->nullable()->after('nombre');
            $table->string('prefijo', 10)->nullable()->after('codigo');
            $table->string('descripcion')->nullable()->after('prefijo');
            $table->unsignedTinyInteger('longitud_numero')->default(7)->after('descripcion');
            $table->boolean('activo')->default(true)->after('longitud_numero');
        });

        DB::table('comprobantes')
            ->where('nombre', 'Boleta')
            ->update([
                'codigo' => 'BOLETA',
                'prefijo' => 'B',
                'descripcion' => 'Comprobante simplificado para venta mostrador',
            ]);

        DB::table('comprobantes')
            ->where('nombre', 'Factura')
            ->update([
                'codigo' => 'FACTURA',
                'prefijo' => 'F',
                'descripcion' => 'Comprobante fiscal para cliente con datos fiscales',
            ]);

        Schema::table('comprobantes', function (Blueprint $table) {
            $table->unique('codigo');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->dropColumn([
                'codigo',
                'prefijo',
                'descripcion',
                'longitud_numero',
                'activo',
            ]);
        });
    }
};
