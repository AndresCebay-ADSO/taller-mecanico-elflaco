<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix I03: Add performance indexes to heavily queried tables.
     */
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->index(['product_id', 'remaining_stock', 'purchased_at'], 'idx_batches_fifo');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index(['product_id', 'movement_type'], 'idx_movements_product_type');
        });

        Schema::table('sale_products', function (Blueprint $table) {
            $table->index('sale_id', 'idx_sale_products_sale_id');
        });

        Schema::table('workshop_jobs', function (Blueprint $table) {
            $table->index('service_order_id', 'idx_jobs_service_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropIndex('idx_batches_fifo');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('idx_movements_product_type');
        });

        Schema::table('sale_products', function (Blueprint $table) {
            $table->dropIndex('idx_sale_products_sale_id');
        });

        Schema::table('workshop_jobs', function (Blueprint $table) {
            $table->dropIndex('idx_jobs_service_order');
        });
    }
};
