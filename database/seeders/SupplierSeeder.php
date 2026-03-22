<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('suppliers')->insert([
            
            [
                'name' => 'Eduardo', // BY andres cebay
                'phone' => '3102212260', // BY andres cebay
                'address' => 'Pitalito, Huila', // BY andres cebay
                'email' => null, // BY andres cebay
                'created_at' => now(), // BY andres cebay
                'updated_at' => now(), // BY andres cebay
            ],

            [
                'name' => 'Johan Sebastián', // BY andres cebay
                'phone' => '3102212260', // BY andres cebay
                'address' => 'Pitalito', // BY andres cebay
                'email' => null, // BY andres cebay
                'created_at' => now(), // BY andres cebay
                'updated_at' => now(), // BY andres cebay
            ],

            [
                'name' => 'Pinajos Motos', // BY andres cebay
                'phone' => '3168691236', // BY andres cebay
                'address' => 'Calle 10 #7 -47 Sucre', // BY andres cebay
                'email' => null, // BY andres cebay
                'created_at' => now(), // BY andres cebay
                'updated_at' => now(), // BY andres cebay
            ],

        ]);
    }
}