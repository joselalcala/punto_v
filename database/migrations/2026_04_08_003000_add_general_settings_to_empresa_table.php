<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->unsignedInteger('stock_minimo_alerta')->default(5)->after('ubicacion');
            $table->boolean('modo_impuesto_incluido')->default(false)->after('stock_minimo_alerta');
            $table->json('metodos_pago_habilitados')->nullable()->after('modo_impuesto_incluido');
        });
    }

    public function down(): void
    {
        Schema::table('empresa', function (Blueprint $table) {
            $table->dropColumn([
                'stock_minimo_alerta',
                'modo_impuesto_incluido',
                'metodos_pago_habilitados',
            ]);
        });
    }
};
