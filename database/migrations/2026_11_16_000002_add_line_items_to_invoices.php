<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Copia de las líneas de la factura al emitirla (decisión 11): al anularla, la orden vuelve a «En progreso» y se
 * puede corregir, pero la factura ya emitida se sigue imprimiendo igual. Las facturas anteriores quedan en null y
 * leen las líneas de su orden; reciben su copia al anularse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('line_items')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('line_items');
        });
    }
};
