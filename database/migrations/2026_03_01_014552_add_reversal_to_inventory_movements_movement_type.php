<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ampliar el ENUM de movement_type para incluir 'reversal'
        DB::statement("ALTER TABLE `inventory_movements` MODIFY `movement_type` ENUM('purchase', 'sale', 'job_usage', 'adjustment', 'reversal') NOT NULL");
    }

    public function down(): void
    {
        // Revertir al ENUM original (sin 'reversal')
        DB::statement("ALTER TABLE `inventory_movements` MODIFY `movement_type` ENUM('purchase', 'sale', 'job_usage', 'adjustment') NOT NULL");
    }
};
