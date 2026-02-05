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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained(); //Referencia al producto
            $table->enum('movementType', ['purchase', 'sale', 'obUsage', 'adjustment'])->index(); //Tipo de movimiento
            $table->integer('quantity');
            $table->decimal('unitPrice', 8, 2)->nullable(); //Precio unitario (puede ser nulo para ciertos movimientos)
            $table->foreignId('supplier_id')->nullable()->constrained(); //Referencia al proveedor (solo para compras)
            $table->string('reference')->nullable()->index(); //Referencia externa (factura, nota de venta, etc)
            $table->string('notes')->nullable(); //Notas adicionales
            $table->date('movementDate'); //Fecha del movimiento
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
