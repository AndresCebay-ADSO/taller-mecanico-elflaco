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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            //FKs
            $table->foreignId('service_order_id')->constrained('service_orders')->nullable(); //Referencia a la orden de servicio
            $table->foreignId('job_type_id')->constrained('job_types'); //Referencia al tipo de trabajo
            $table->foreignId('mechanic_id')->constrained('mechanics')->nullable(); //Referencia al mecanico asignado
            //INfo cliente y trabajo
            $table->string('customerName'); //Nombre del cliente
            $table->string('customerPhone'); //Telefono del cliente
            $table->string('vehicleInfo'); // Informacion del vehiculo (marca, modelo, año, color, etc)
            $table->string('description'); //Descripcion del trabajo y como entro el vehiculo al taller
            //Costos
            $table->decimal('laborCost', 8, 2); //Costo de la mano de obra
            $table->decimal('mechanicCost', 8, 2); //Costo asignado al mecanico
            $table->decimal('workshopCost', 10, 2); //Costo asignado al taller
            $table->decimal('totalAmount', 10, 2); //Costo total del trabajo
            //Estado del trabajo
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending')->index(); //Estado del trabajo
            $table->timestamp('startedAt')->nullable(); //Fecha de inicio del trabajo
            $table->timestamp('completedAt')->nullable(); //Fecha de finalizacion del trabajo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
