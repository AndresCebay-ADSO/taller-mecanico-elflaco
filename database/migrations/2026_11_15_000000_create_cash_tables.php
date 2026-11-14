<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Caja diaria por sucursal.
     *
     * cash_days: un registro por sucursal y fecha. Se crea la primera vez que se usa la caja ese día
     * (base, un gasto o el cierre). Al cerrar se guardan las cifras del momento, para que el
     * historial no cambie si después se anula algo.
     *
     * cash_movements: lo que entra o sale de la caja aparte de ventas y comprobantes
     * (gastos, pagos a mecánicos, compras de repuestos, retiros, ingresos extra).
     */
    public function up(): void
    {
        Schema::create('cash_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('opening_cash', 12, 2)->default(0);

            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('counted_cash', 12, 2)->nullable();
            $table->decimal('withdrawn_cash', 12, 2)->nullable();
            $table->json('closing_summary')->nullable();
            $table->string('closing_notes', 1000)->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'date']);
        });

        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_day_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('concept', 255);
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30)->default('Efectivo');
            $table->foreignId('mechanic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'mechanic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_days');
    }
};
