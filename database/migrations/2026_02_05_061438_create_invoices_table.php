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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoiceNumber')->unique(); //Número de factura
            $table->foreign('service_order_id')->references('id')->on('service_orders'); //Referencia a la orden de servicio
            $table->decimal('amount', 10, 2); //Monto total de la factura
            $table->date('invoiceDate')->index(); //Fecha de la factura
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
