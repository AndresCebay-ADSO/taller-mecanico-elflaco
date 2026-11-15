<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Decisión 10: «Mano de obra variable» y «total fijo por porcentaje» se guardaban pero no hacían nada.
 * Un precio fijo se hace con el cálculo «Monto fijo»; la mano de obra de los de porcentaje se escribe en cada trabajo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_types', function (Blueprint $table) {
            $table->dropColumn(['allow_custom_labor', 'percentage_fixed_total']);
        });
    }

    public function down(): void
    {
        Schema::table('job_types', function (Blueprint $table) {
            $table->decimal('percentage_fixed_total', 10, 2)->nullable();
            $table->boolean('allow_custom_labor')->default(true);
        });
    }
};
