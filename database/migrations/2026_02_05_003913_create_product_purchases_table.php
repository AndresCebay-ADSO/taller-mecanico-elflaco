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
        Schema::create('product_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained(); //Referencia al producto comprado
            $table->foreignId('supplier_id')->constrained(); //Referencia a la compra
            $table->decimal('unit_price', 10, 2); //Precio unitario
            $table->decimal('purchase_price', 10, 2); //Precio de compra
            $table->integer('quantity'); //cantidad comprada
            $table->string('note')->nullable(); //Nota adicional sobre la compra
            
            $table->date('purchase_date')->index(); //Fecha de la compra
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_purchases');
    }
};
