<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La factura guarda también el cliente y la moto como estaban al emitirla (decisión 11, revisión externa): editar la
 * orden después no cambia una factura ya emitida. Las anteriores quedan en null y los leen de su orden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('customer_snapshot')->nullable()->after('line_items');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('customer_snapshot');
        });
    }
};
