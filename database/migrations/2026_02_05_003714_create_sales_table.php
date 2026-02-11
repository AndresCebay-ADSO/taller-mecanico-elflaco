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
        Schema::create('sales', function (Blueprint $table) { //Representa una venta directa al cliente
            $table->id();
            $table->string('customer_name'); //Nombre del cliente
            $table->decimal('total_amount', 12, 2);
            $table->date('sale_date')->index(); //Fecha de la venta 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
