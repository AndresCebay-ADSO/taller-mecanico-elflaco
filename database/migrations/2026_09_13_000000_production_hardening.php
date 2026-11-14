<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amplia columnas monetarias (montos en COP superan 999.999,99) y permite desactivar usuarios.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('is_admin');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->nullable()->change();
        });

        Schema::table('sale_products', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->change();
            $table->decimal('total_price', 14, 2)->change();
        });

        Schema::table('job_products', function (Blueprint $table) {
            $table->decimal('unit_price', 12, 2)->change();
            $table->decimal('total_price', 14, 2)->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total_amount', 14, 2)->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount', 14, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->change();
        });

        Schema::table('job_products', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->change();
            $table->decimal('total_price', 12, 2)->change();
        });

        Schema::table('sale_products', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->change();
            $table->decimal('total_price', 10, 2)->change();
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_price', 8, 2)->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
