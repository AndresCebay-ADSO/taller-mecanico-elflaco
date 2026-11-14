<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix A02: Create branch_user pivot table for explicit user assignment to branches.
     */
    public function up(): void
    {
        Schema::create('branch_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['branch_id', 'user_id']);
        });

        // Bug 5 fix: Perform unbounded inserts at database engine level instead of RAM.
        // Assign existing users to all active branches to maintain backwards compatibility
        DB::statement('
            INSERT INTO branch_user (branch_id, user_id, created_at, updated_at)
            SELECT branches.id, users.id, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            FROM branches
            CROSS JOIN users
            WHERE branches.is_active = 1
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
