<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 1. Migra los supplier_id existentes a la tabla pivote product_supplier.
     * 2. Hace nullable supplier_id en products (mantiene columna por compatibilidad histórica).
     */
    public function up(): void
    {
        // Migrar datos existentes a la tabla pivote antes de alterar la columna
        $existing = DB::table('products')
            ->whereNotNull('supplier_id')
            ->select('id', 'supplier_id')
            ->get();

        foreach ($existing as $product) {
            DB::table('product_supplier')->insertOrIgnore([
                'product_id'  => $product->id,
                'supplier_id' => $product->supplier_id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // Ahora hacer nullable la columna
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable(false)->change();
        });
    }
};
