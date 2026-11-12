<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cédula o NIT del cliente: sale en el comprobante de servicio. Opcional.
     */
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('customer_document', 20)->nullable()->after('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn('customer_document');
        });
    }
};
