<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix B02: Drop legacy unused product_purchases table.
     */
    public function up(): void
    {
        Schema::dropIfExists('product_purchases');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('product_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->date('purchase_date');
            $table->timestamps();
        });
    }
};
