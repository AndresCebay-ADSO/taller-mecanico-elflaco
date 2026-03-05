<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Mechanic;
use App\Models\JobType;
use App\Models\ServiceOrder;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Models\User;

class PaginationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        $user = User::first() ?? User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
        ]);

        // 1. Proveedores (30 records)
        for ($i = 0; $i < 30; $i++) {
            Supplier::create([
                'name' => $faker->company,
                'phone' => $faker->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'address' => $faker->address,
            ]);
        }
        $suppliers = Supplier::all();

        // 2. Productos (30 records)
        $categories = ['Baterías', 'Frenos', 'Filtros', 'Aceites', 'Llantas', 'Accesorios', 'Luces'];
        for ($i = 0; $i < 30; $i++) {
            $purchase = $faker->randomFloat(2, 5, 200);
            Product::create([
                'name' => 'Producto ' . $faker->word . ' ' . $faker->ean8,
                'category' => $faker->randomElement($categories),
                'supplier_id' => $suppliers->random()->id,
                'purchase_price' => $purchase,
                'sale_price' => $purchase * 1.3, // 30% margin
                'stock' => $faker->numberBetween(10, 100),
                'min_stock' => $faker->numberBetween(5, 10),
                'upc' => $faker->unique()->ean13,
            ]);
        }
        $products = Product::all();

        // 3. Mecánicos (30 records)
        for ($i = 0; $i < 30; $i++) {
            Mechanic::create([
                'name' => $faker->name,
                'phone' => $faker->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'hire_date' => $faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                'is_active' => $faker->boolean(90), // 90% chance of being active
            ]);
        }

        // 4. Tipos de Trabajo (30 records)
        $jobTypeNames = ['Cambio de', 'Revisión de', 'Ajuste de', 'Reparación de', 'Mantenimiento de'];
        $jobTypeParts = ['Frenos', 'Motor', 'Suspensión', 'Transmisión', 'Eje', 'Cadena', 'Batería'];
        
        for ($i = 0; $i < 30; $i++) {
            $calcType = $faker->randomElement(['percentage', 'fixed']);
            JobType::create([
                'name' => $faker->randomElement($jobTypeNames) . ' ' . $faker->randomElement($jobTypeParts) . ' ' . $faker->word,
                'description' => $faker->sentence(),
                'default_description' => null,
                'calculation_type' => $calcType,
                'mechanic_percentage' => $calcType === 'percentage' ? 60 : null,
                'workshop_percentage' => $calcType === 'percentage' ? 40 : null,
                'fixed_mechanic_amount' => $calcType === 'fixed' ? $faker->numberBetween(100, 500) : null,
                'fixed_workshop_amount' => $calcType === 'fixed' ? $faker->numberBetween(50, 300) : null,
                'allow_products' => $faker->boolean(80),
                'allow_custom_labor' => $faker->boolean(),
                'is_active' => $faker->boolean(95),
                'is_system' => false,
            ]);
        }

        // 5. Órdenes de Servicio (30 records)
        $statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        for ($i = 0; $i < 30; $i++) {
            ServiceOrder::create([
                'customer_name' => $faker->name,
                'customer_phone' => $faker->phoneNumber,
                'vehicle_info' => $faker->word . ' ' . $faker->year,
                'service_description' => $faker->paragraph(),
                'status' => $faker->randomElement($statuses),
                'started_at' => $faker->dateTimeBetween('-1 month', 'now'),
            ]);
        }

        // 6. Ventas (30 records)
        $paymentMethods = ['Efectivo', 'Transferencia', 'Tarjeta', 'Otro'];
        for ($i = 0; $i < 30; $i++) {
            $sale = Sale::create([
                'customer_name' => $faker->name,
                'total_amount' => 0, // Calculated later
                'sale_date' => clone $faker->dateTimeBetween('-1 month', 'now'), // Must be date instance
                'payment_method' => $faker->randomElement($paymentMethods),
                'user_id' => $user->id,
                'status' => $faker->boolean(90) ? 'completada' : 'anulada',
                'created_at' => $faker->dateTimeBetween('-1 month', 'now'),
            ]);

            // Añadir 1 o 2 productos a la venta
            $saleTotal = 0;
            $itemsCount = $faker->numberBetween(1, 2);
            for ($j = 0; $j < $itemsCount; $j++) {
                $product = $products->random();
                $qty = $faker->numberBetween(1, 4);
                $totalItem = $product->sale_price * $qty;
                
                $sale->saleProducts()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $product->sale_price,
                    'total_price' => $totalItem,
                ]);

                $saleTotal += $totalItem;
                
                // Generar movimiento de inventario simulado (sin decrementar para no dejar sin stock la BD de prueba)
                DB::table('inventory_movements')->insert([
                    'product_id' => $product->id,
                    'movement_type' => 'sale',
                    'quantity' => -$qty,
                    'unit_price' => $product->sale_price,
                    'reference' => "Venta #{$sale->id}",
                    'movement_date' => $sale->created_at,
                    'created_at' => $sale->created_at,
                    'updated_at' => $sale->created_at,
                ]);
            }

            $sale->update(['total_amount' => $saleTotal]);
        }
    }
}
