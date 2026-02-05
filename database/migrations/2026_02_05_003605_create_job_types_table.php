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
        Schema::create('job_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('calculationType', ['percentage', 'fixed']); //Metodo de calculo del costo

            //Campos para metodo de porcentaje
            $table->decimal('mechanicPercentage', 8, 2)->nullable(); //Tarifa del mecanico para este tipo de trabajo
            $table->decimal('workshopPercentage', 10, 2)->nullable(); //Tarifa del taller para este tipo de trabajo
            $table->decimal('fixedTotal', 10, 2)->nullable(); //Costo total fijo (si aplica)
            //Campos para metodo fijo
            $table->decimal('fixedMechanicAmount', 8, 2)->nullable(); //Monto fijo para el mecanico (si aplica)
            $table->decimal('fixedWorkshopAmount', 10, 2)->nullable(); //Monto fijo para el taller (si aplica)
            //Opciones adicionales
            $table->boolean('allowProducts')->default(true); //Indica si se pueden agregar productos a este tipo de trabajo
            $table->boolean('allowCustomLabor')->default(true); //Indica si se puede agregar mano de obra personalizada
            //Estado del tipo de trabajo
            $table->boolean('isActive')->default(true); //Estado del tipo de trabajo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_types');
    }
};
