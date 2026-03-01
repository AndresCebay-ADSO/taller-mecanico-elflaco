<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ampliar el ENUM para incluir 'reversal' (anulaciones de ventas) y 'job' (alias defensivo)
        DB::statement("ALTER TABLE `inventory_movements` MODIFY `movement_type` ENUM('purchase', 'sale', 'job_usage', 'adjustment', 'reversal') NOT NULL");
    }

    public function down(): void
    {
        // Bug #3: Verificar si ya existen movimientos 'reversal' antes de eliminar el valor del ENUM.
        // Si existen, el rollback fallaría o corrompería los datos, así que se aborta con error claro.
        $hasReversal = DB::table('inventory_movements')
            ->where('movement_type', 'reversal')
            ->exists();

        if ($hasReversal) {
            throw new \RuntimeException(
                'No se puede revertir esta migración: existen registros con movement_type="reversal". ' .
                'Elimínalos manualmente antes de hacer rollback.'
            );
        }

        DB::statement("ALTER TABLE `inventory_movements` MODIFY `movement_type` ENUM('purchase', 'sale', 'job_usage', 'adjustment') NOT NULL");
    }
};
