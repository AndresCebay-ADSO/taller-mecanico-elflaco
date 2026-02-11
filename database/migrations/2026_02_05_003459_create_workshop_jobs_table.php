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
        Schema::create('workshop_jobs', function (Blueprint $table) {
            $table->id();
            //FKs
            $table->foreignId('service_order_id')
                ->nullable()
                ->constrained('service_orders')
                ->nullOnDelete(); // Referencia a la orden de servicio
            $table->foreignId('job_type_id')
                ->constrained('job_types')
                ->restrictOnDelete(); // Referencia al tipo de trabajo
            $table->foreignId('mechanic_id')
                ->nullable()
                ->constrained('mechanics')
                ->nullOnDelete(); // Referencia al mecanico asignado
            //INfo cliente y trabajo
            $table->string('customer_name'); //Nombre del cliente
            $table->string('customer_phone'); //Telefono del cliente
            $table->string('vehicle_info'); // Informacion del vehiculo (marca, modelo, año, color, etc)
            $table->string('description'); //Descripcion del trabajo y como entro el vehiculo al taller
            //Costos
            $table->decimal('labor_cost', 10, 2); //Costo de la mano de obra
            $table->decimal('mechanic_cost', 10, 2); //Costo asignado al mecanico
            $table->decimal('workshop_cost', 10, 2); //Costo asignado al taller
            $table->decimal('total_amount', 12, 2); //Costo total del trabajo
            //Estado del trabajo
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending')->index(); //Estado del trabajo
            $table->timestamp('started_at')->nullable(); //Fecha de inicio del trabajo
            $table->timestamp('completed_at')->nullable(); //Fecha de finalizacion del trabajo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_jobs');
    }
};
