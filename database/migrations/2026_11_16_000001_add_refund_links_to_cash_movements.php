<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «Devolución a cliente» (decisión 2): la salida de caja queda ligada a la venta o factura anulada.
 * Únicos: una venta o factura se devuelve una sola vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->unique()->after('batch_id')->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->unique()->after('sale_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropConstrainedForeignId('sale_id');
        });
    }
};
