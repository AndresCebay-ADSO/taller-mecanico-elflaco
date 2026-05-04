<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Supplier::updateOrCreate(
            ['email' => null, 'name' => 'Eduardo'],
            [
                'phone' => '31022122600',
                'address' => 'Pitalito, Huila',
                'email' => null,
                'active' => true,
            ]
        );

        Supplier::updateOrCreate(
            ['email' => null, 'name' => 'Johan Sebastián'],
            [
                'phone' => '31022122600',
                'address' => 'Pitalito',
                'email' => null,
                'active' => true,
            ]
        );

        Supplier::updateOrCreate(
            ['email' => null, 'name' => 'Pinajos Motos'],
            [
                'phone' => '31686912360',
                'address' => 'Calle 10 #7 -47 Sucre',
                'email' => null,
                'active' => true,
            ]
        );
    }
}