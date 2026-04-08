<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->foreignId('ubicacione_id')
                ->nullable()
                ->after('user_id')
                ->constrained('ubicaciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ubicacione_id');
        });
    }
};
