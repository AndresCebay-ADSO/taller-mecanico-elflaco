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
            $table->text('default_description')->nullable();
            $table->enum('calculation_type', ['percentage', 'fixed']); // Método de cálculo del costo

            // Campos para método porcentaje
            $table->decimal('mechanic_percentage', 5, 2)->nullable(); // Tarifa del mecánico (%)
            $table->decimal('workshop_percentage', 5, 2)->nullable(); // Tarifa del taller (%)
            $table->decimal('percentage_fixed_total', 10, 2)->nullable(); // Monto total fijo opcional

            // Campos para método fijo
            $table->decimal('fixed_mechanic_amount', 10, 2)->nullable(); // Monto fijo para el mecánico
            $table->decimal('fixed_workshop_amount', 10, 2)->nullable(); // Monto fijo para el taller

            // Opciones adicionales
            $table->boolean('allow_products')->default(true); // Permite agregar productos
            $table->boolean('allow_custom_labor')->default(true); // Permite mano de obra personalizada

            // Estado del tipo de trabajo
            $table->boolean('is_active')->default(true); // Estado del tipo de trabajo
            $table->boolean('is_system')->default(false);
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
