<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->foreignId('supplier_id')
                ->constrained()
                ->restrictOnDelete(); // Evitar productos huérfanos si se borra proveedor
            $table->decimal('purchase_price', 10, 2); // Precio de compra
            $table->decimal('sale_price', 10, 2); // Precio de venta
            $table->unsignedInteger('stock');
            $table->unsignedInteger('min_stock'); // Umbral para alertas de stock bajo
            $table->string('upc')->unique(); // Código de barras (universal product code)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
