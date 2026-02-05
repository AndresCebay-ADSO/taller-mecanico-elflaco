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
            $table->foreignId('supplier_id')->constrained(); //https://laravel.com/docs/12.x/migrations#foreign-key-constraints
            $table->string('purchasePrice'); //Precio de compra
            $table->string('salePrice'); //Precio de venta
            $table->integer('stock');
            $table->integer('minStock'); //Hacer el uso de este para las alertas de stock bajo
            $table->date('createdAt'); //Fecha de creacion
            $table->date('updatedAt')->nullable(); //Fecha de ultima actualizacion
            $table->string('upc')->unique(); //codigo de barra (universal product code)
            
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
