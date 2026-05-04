<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix M04: Run migration to copy selling_price to sale_price where sale_price is null,
     * then drop the duplicated selling_price column to avoid bugs.
     */
    public function up(): void
    {
        // Copy data first
        DB::table('batches')
            ->whereNull('sale_price')
            ->update(['sale_price' => DB::raw('selling_price')]);

        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('selling_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->decimal('selling_price', 10, 2)->after('cost_price')->nullable();
        });

        // Copy data back
        DB::table('batches')
            ->whereNotNull('sale_price')
            ->update(['selling_price' => DB::raw('sale_price')]);
    }
};
