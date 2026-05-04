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

        // Assign existing users to all active branches to maintain backwards compatibility
        // until an admin UI is built for user branch assignment.
        $branches = DB::table('branches')->where('is_active', true)->pluck('id');
        $users = DB::table('users')->pluck('id');

        $inserts = [];
        foreach ($users as $userId) {
            foreach ($branches as $branchId) {
                $inserts[] = [
                    'branch_id' => $branchId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        if (!empty($inserts)) {
            DB::table('branch_user')->insert($inserts);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_user');
    }
};
