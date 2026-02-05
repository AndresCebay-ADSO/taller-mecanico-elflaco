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
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('customerName')->index();
            $table->string('customerPhone')->nullable();
            $table->string('vehicleInfo'); //Detalles del vehiculo (marca, modelo, año, color, etc)
            $table->text('serviceDescription'); //Descripcion del servicio solicitado

            //Estado del servicio, puede ser: pendiente, en progreso, completado, cancelado
            $table->enum('status', ['pending', 
            'in_progress', 
            'completed', 
            'cancelled'])->default('pending')->index();

            $table->timestamp('startedAt')->nullable(); //Fecha de inicio del servicio
            $table->timestap('completedAt')->nullable(); //Fecha de finalizacion del servicio
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
