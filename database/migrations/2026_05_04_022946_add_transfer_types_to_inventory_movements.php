<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix C01: Expandir el ENUM de movement_type para incluir tipos de transferencia.
     * Sin estos valores, BranchTransferController::completeTransfer() falla en MySQL strict mode.
     */
    public function up(): void
    {
        // Cambiar de ENUM restrictivo a string para mayor flexibilidad
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('movement_type', 30)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Solo revertir si no hay valores fuera del ENUM original
        $hasTransferTypes = DB::table('inventory_movements')
            ->whereIn('movement_type', ['transfer_in', 'transfer_out'])
            ->exists();

        if (!$hasTransferTypes) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->enum('movement_type', [
                    'purchase', 'sale', 'job_usage', 'adjustment', 'reversal',
                ])->change();
            });
        }
    }
};
