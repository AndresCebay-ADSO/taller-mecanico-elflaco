<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Branch::count() === 0) {
            Branch::create([
                'name' => 'Sede Principal',
                'address' => null,
                'phone' => null,
                'email' => null,
                'is_active' => true,
            ]);
        }
    }
}
