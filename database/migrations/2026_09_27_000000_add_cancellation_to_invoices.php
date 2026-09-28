<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anular una factura ya no la borra: queda registrada como anulada (con fecha y usuario)
     * y la orden puede volver a facturarse. Por eso la orden deja de ser única en `invoices`;
     * la regla "una factura vigente por orden" la garantiza InvoiceService con bloqueo de fila.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('invoice_date');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();

            // La llave foránea de service_order_id necesita un índice propio antes de soltar el único (MySQL).
            $table->index('service_order_id', 'invoices_service_order_id_index');
            $table->dropUnique('invoices_service_order_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('service_order_id', 'invoices_service_order_id_unique');
            $table->dropIndex('invoices_service_order_id_index');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn('cancelled_at');
        });
    }
};
