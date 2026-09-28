<?php

namespace Database\Seeders;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\BranchTransfer;
use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WorkshopJob;
use App\Services\BranchTransferService;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\SaleService;
use App\Services\WorkshopJobService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Datos de demostración para presentar el sistema al cliente.
 *
 * Uso (en una base de datos de demo, nunca en la real):
 *   php artisan migrate:fresh --seed --seeder=DemoSeeder
 *
 * Simula ~60 días de operación de un taller de motos con dos sedes usando los mismos
 * servicios que la aplicación, así stock, lotes FIFO, ventas, órdenes y facturas quedan consistentes.
 */
class DemoSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Demo2026!';

    private const DAYS_OF_HISTORY = 60;

    /** Últimos días sin reabastecer, para que el tablero muestre alertas de stock bajo. */
    private const DAYS_WITHOUT_RESTOCK = 6;

    private const SETTINGS = [
        'workshop_name' => 'MotoTaller El Flaco',
        'workshop_nit' => '901.234.567-8',
        'workshop_phone' => '+57 310 555 0101',
        'workshop_email' => 'contacto@elflaco.test',
        'workshop_address' => 'Cra. 4 # 8-25, Pitalito, Huila',
        'tax_percentage' => '19',
        'footer_text_invoice' => 'Gracias por confiar en MotoTaller El Flaco. Garantía de 30 días en mano de obra.',
    ];

    /** [nombre, teléfono, dirección] */
    private const SUPPLIERS = [
        ['Pinajos Motos', '3168691236', 'Calle 10 # 7-47, Sucre'],
        ['Lubricantes del Huila', '3102212260', 'Zona industrial, Pitalito'],
        ['Distrimotos Neiva', '3004567812', 'Cra. 5 # 12-30, Neiva'],
        ['Llantas y Rines del Sur', '3157894561', 'Calle 5 # 9-14, Garzón'],
        ['Eléctricos La Quinta', '3209871234', 'Calle 5 # 4-22, Pitalito'],
    ];

    /** [nombre, categoría, costo, precio venta, stock mínimo, proveedores, cantidad sede principal, cantidad sede centro, código] */
    private const CATALOG = [
        ['Aceite Motul 5100 20W50 1L', 'Lubricantes', 32000, 45000, 12, ['Lubricantes del Huila'], 40, 20, '7702001000011'],
        ['Aceite Mobil Super Moto 20W50 1L', 'Lubricantes', 26000, 38000, 10, ['Lubricantes del Huila'], 36, 18, '7702001000028'],
        ['Filtro de aceite Pulsar 180/200', 'Filtros', 9000, 16000, 6, ['Pinajos Motos', 'Distrimotos Neiva'], 24, 10, '7702001000035'],
        ['Filtro de aire Yamaha FZ 2.0', 'Filtros', 18000, 30000, 4, ['Distrimotos Neiva'], 12, 6, '7702001000042'],
        ['Bujía NGK CR7HSA', 'Encendido', 9500, 17000, 10, ['Distrimotos Neiva', 'Eléctricos La Quinta'], 50, 24, '7702001000059'],
        ['Kit de arrastre AKT NKD 125', 'Transmisión', 68000, 98000, 3, ['Pinajos Motos'], 8, 4, '7702001000066'],
        ['Kit de arrastre Pulsar 200 NS', 'Transmisión', 115000, 165000, 3, ['Pinajos Motos', 'Distrimotos Neiva'], 6, 3, '7702001000073'],
        ['Pastillas de freno delanteras FZ', 'Frenos', 22000, 38000, 5, ['Distrimotos Neiva'], 18, 8, '7702001000080'],
        ['Bandas de freno traseras Boxer', 'Frenos', 15000, 26000, 5, ['Pinajos Motos'], 14, 7, '7702001000097'],
        ['Líquido de frenos DOT 4 250ml', 'Frenos', 11000, 19000, 4, ['Lubricantes del Huila'], 15, 6, '7702001000103'],
        ['Llanta Michelin Pilot Street 90/90-18', 'Llantas', 185000, 245000, 2, ['Llantas y Rines del Sur'], 6, 3, '7702001000110'],
        ['Llanta IRC 110/80-17 trasera', 'Llantas', 165000, 220000, 2, ['Llantas y Rines del Sur'], 5, 2, '7702001000127'],
        ['Batería Yuasa YTX7L-BS', 'Eléctricos', 210000, 285000, 2, ['Eléctricos La Quinta'], 5, 2, '7702001000134'],
        ['Bombillo H4 12V 35/35W', 'Eléctricos', 6000, 12000, 8, ['Eléctricos La Quinta'], 30, 12, '7702001000141'],
        ['Guaya de clutch Boxer CT100', 'Transmisión', 9000, 16000, 5, ['Pinajos Motos'], 16, 6, '7702001000158'],
        ['Cadenilla distribución Pulsar 200', 'Motor', 38000, 60000, 2, ['Distrimotos Neiva'], 5, 2, '7702001000165'],
        ['Kit de pistón completo Pulsar 200', 'Motor', 420000, 560000, 1, ['Distrimotos Neiva'], 3, 1, '7702001000172'],
        ['Grasa multipropósito 250g', 'Lubricantes', 8000, 14000, 4, ['Lubricantes del Huila'], 12, 5, '7702001000189'],
        ['Amortiguador trasero YSS Pulsar', 'Suspensión', 780000, 1150000, 1, ['Distrimotos Neiva'], 2, 0, '7702001000196'],
    ];

    /** [nombre, descripción, tipo, mecánico (% o monto), taller (% o monto), permite productos, mano de obra mín, máx, frecuencia] */
    private const JOB_TYPES = [
        ['Cambio de aceite', 'Cambio de aceite de motor y revisión de niveles', 'fixed', 8000, 7000, true, 0, 0, 30],
        ['Mantenimiento general', 'Mantenimiento preventivo completo', 'percentage', 60, 40, true, 45000, 70000, 20],
        ['Sincronización', 'Ajuste de carburación, bujía y válvulas', 'percentage', 50, 50, true, 35000, 50000, 12],
        ['Cambio de kit de arrastre', 'Cambio de cadena, piñón y corona', 'fixed', 15000, 10000, true, 0, 0, 10],
        ['Revisión de frenos', 'Revisión y ajuste del sistema de frenos', 'percentage', 60, 40, true, 20000, 35000, 12],
        ['Diagnóstico eléctrico', 'Revisión del sistema eléctrico y luces', 'percentage', 50, 50, true, 25000, 60000, 8],
        ['Lavado y engrase', 'Lavado general y lubricación de partes móviles', 'fixed', 6000, 6000, false, 0, 0, 6],
        ['Reparación de motor', 'Reparación interna de motor', 'percentage', 55, 45, true, 250000, 400000, 2],
    ];

    /** Repuestos por tipo de trabajo: [producto o grupo, probabilidad %, cantidad] */
    private const JOB_PARTS = [
        'Cambio de aceite' => [['@aceite', 100, 1], ['Filtro de aceite Pulsar 180/200', 50, 1]],
        'Mantenimiento general' => [['@aceite', 100, 1], ['Bujía NGK CR7HSA', 80, 1], ['Filtro de aire Yamaha FZ 2.0', 30, 1], ['Grasa multipropósito 250g', 60, 1]],
        'Sincronización' => [['Bujía NGK CR7HSA', 100, 1], ['Filtro de aire Yamaha FZ 2.0', 40, 1]],
        'Cambio de kit de arrastre' => [['@kit', 100, 1]],
        'Revisión de frenos' => [['@freno', 100, 1], ['Líquido de frenos DOT 4 250ml', 50, 1]],
        'Diagnóstico eléctrico' => [['Bombillo H4 12V 35/35W', 50, 1], ['Batería Yuasa YTX7L-BS', 20, 1]],
        'Lavado y engrase' => [],
        'Reparación de motor' => [['Kit de pistón completo Pulsar 200', 100, 1], ['Cadenilla distribución Pulsar 200', 100, 1], ['@aceite', 100, 1]],
    ];

    private const PART_GROUPS = [
        '@aceite' => ['Aceite Motul 5100 20W50 1L', 'Aceite Mobil Super Moto 20W50 1L'],
        '@kit' => ['Kit de arrastre AKT NKD 125', 'Kit de arrastre Pulsar 200 NS'],
        '@freno' => ['Pastillas de freno delanteras FZ', 'Bandas de freno traseras Boxer'],
    ];

    /** Productos de mostrador y su frecuencia relativa */
    private const COUNTER_ITEMS = [
        'Aceite Motul 5100 20W50 1L' => 18,
        'Aceite Mobil Super Moto 20W50 1L' => 16,
        'Bujía NGK CR7HSA' => 14,
        'Bombillo H4 12V 35/35W' => 10,
        'Guaya de clutch Boxer CT100' => 8,
        'Filtro de aceite Pulsar 180/200' => 8,
        'Líquido de frenos DOT 4 250ml' => 6,
        'Grasa multipropósito 250g' => 6,
        'Pastillas de freno delanteras FZ' => 5,
        'Bandas de freno traseras Boxer' => 5,
        'Llanta IRC 110/80-17 trasera' => 2,
        'Batería Yuasa YTX7L-BS' => 2,
    ];

    private const PAYMENT_METHODS = ['Efectivo' => 45, 'Nequi' => 25, 'Daviplata' => 10, 'Transferencia' => 10, 'Tarjeta' => 10];

    /** [nombre, teléfono, meses de antigüedad] */
    private const MECHANICS = [
        'Sede Principal' => [['Jhon Fredy Ortiz', '3114567001', 38], ['Carlos Andrés Díaz', '3124567002', 20], ['Wilmer Castro', '3134567003', 7]],
        'Sede Centro' => [['Luis Eduardo Muñoz', '3144567004', 26], ['Brayan Stiven Rojas', '3154567005', 4]],
    ];

    /** [cliente, teléfono, moto y placa] */
    private const CUSTOMERS = [
        ['Juan Camilo Rojas', '3104567890', 'Yamaha FZ 2.0 - JKL45E'],
        ['María Fernanda Silva', '3208765432', 'Honda CB 190R - RTY56G'],
        ['Diego Alejandro Muñoz', '3001237788', 'Bajaj Pulsar NS 200 - QWE78F'],
        ['Yeison Andrés Cruz', '3156549012', 'AKT NKD 125 - MNB23C'],
        ['Paola Andrea Guzmán', '3017894561', 'Suzuki Gixxer 150 - ZXC89H'],
        ['Héctor Fabio Trujillo', '3112345678', 'Bajaj Boxer CT100 - HGF34J'],
        ['Natalia Ospina', '3189012345', 'Yamaha NMAX 155 - ASD45N'],
        ['Jhon Jairo Perdomo', '3125678901', 'Yamaha XTZ 150 - POI67K'],
        ['Luz Mery Cuéllar', '3143456789', 'Honda XR 150L - LKJ90L'],
        ['Cristian David Sánchez', '3056781234', 'TVS Apache RTR 160 - VBN12M'],
        ['Andrea Carolina Vargas', '3167890123', 'AKT CR4 125 - WSX34P'],
        ['Óscar Iván Losada', '3134567012', 'Bajaj Discover 125 - EDC56Q'],
        ['Sandra Milena Polanía', '3191234560', 'Honda Wave 110S - RFV78R'],
        ['Edwin Fernando Tovar', '3029876543', 'Yamaha YBR 125 - TGB90S'],
        ['Katherine Lorena Díaz', '3172345098', 'Suzuki DR 150 - YHN12T'],
    ];

    private const COMPLAINTS = [
        'Mantenimiento de los 10.000 km',
        'Ruido en la cadena al acelerar',
        'Los frenos delanteros no responden bien',
        'No enciende en las mañanas',
        'Cambio de aceite y revisión general',
        'Pierde potencia en las subidas',
        'La luz delantera no funciona',
        'Revisión completa antes de viaje a Neiva',
        'Vibración en el manubrio a alta velocidad',
        'Sale humo blanco del escape',
    ];

    /** @var array<int, array<string, Product>> */
    private array $products = [];

    /** @var array<string, JobType> */
    private array $jobTypes = [];

    /** @var array<int, array<int, Mechanic>> */
    private array $mechanics = [];

    /** @var array<string, Supplier> */
    private array $suppliers = [];

    /** @var array<string, int> */
    private array $stats = ['sales' => 0, 'cancelled_sales' => 0, 'orders' => 0, 'invoices' => 0];

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly SaleService $sales,
        private readonly WorkshopJobService $jobs,
        private readonly InvoiceService $invoices,
        private readonly BranchTransferService $transfers,
    ) {
    }

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('DemoSeeder no puede ejecutarse en producción.');

            return;
        }

        mt_srand(2026);

        $realNow = CarbonImmutable::now();
        $firstDay = $realNow->startOfDay()->subDays(self::DAYS_OF_HISTORY);

        try {
            $this->travelTo($firstDay->subDays(25), 8);

            $this->seedSettings();
            [$principal, $centro] = $this->seedBranches();
            $users = $this->seedUsers($principal, $centro);
            $this->seedSuppliers();
            $this->seedJobTypes();
            $this->seedMechanics($principal);
            $this->seedMechanics($centro);
            $this->seedCatalog($principal, $firstDay, isMain: true);
            $this->seedCatalog($centro, $firstDay, isMain: false);

            for ($day = 0; $day <= self::DAYS_OF_HISTORY; $day++) {
                $date = $firstDay->addDays($day);
                $daysAgo = self::DAYS_OF_HISTORY - $day;

                if ($date->isSunday()) {
                    continue;
                }

                $lastMinute = $daysAgo === 0 ? max(9 * 60, $realNow->hour * 60 + $realNow->minute) : 18 * 60;

                $this->simulateDay($date, $day, $daysAgo, $principal, $users['principal'], true, $lastMinute);
                $this->simulateDay($date, $day, $daysAgo, $centro, $users['centro'], false, $lastMinute);
                $this->branchEvents($date, $day, $daysAgo, $principal, $centro, $users['admin']);
            }
        } finally {
            Carbon::setTestNow();
        }

        $this->printSummary();
    }

    private function seedSettings(): void
    {
        foreach (self::SETTINGS as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /**
     * @return array{0: Branch, 1: Branch}
     */
    private function seedBranches(): array
    {
        $principal = Branch::updateOrCreate(['name' => 'Sede Principal'], [
            'address' => 'Cra. 4 # 8-25, Pitalito',
            'phone' => '3105550101',
            'email' => 'principal@elflaco.test',
            'is_active' => true,
        ]);

        $centro = Branch::updateOrCreate(['name' => 'Sede Centro'], [
            'address' => 'Calle 7 # 3-40, Pitalito',
            'phone' => '3105550202',
            'email' => 'centro@elflaco.test',
            'is_active' => true,
        ]);

        return [$principal, $centro];
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(Branch $principal, Branch $centro): array
    {
        $make = function (string $name, string $email, bool $isAdmin, array $branches, bool $isActive = true): User {
            $user = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => self::DEMO_PASSWORD,
            ]);
            $user->forceFill(['is_admin' => $isAdmin, 'is_active' => $isActive])->save();
            $user->branches()->sync(collect($branches)->pluck('id'));

            return $user;
        };

        return [
            'admin' => $make('Administrador Demo', 'admin@elflaco.test', true, []),
            'principal' => $make('Laura Gómez', 'caja.principal@elflaco.test', false, [$principal]),
            'centro' => $make('Andrés Muñoz', 'caja.centro@elflaco.test', false, [$centro]),
            'jefe' => $make('Jefe de Taller', 'jefe.taller@elflaco.test', false, [$principal, $centro]),
            'inactivo' => $make('Exempleado', 'exempleado@elflaco.test', false, [$principal], false),
        ];
    }

    private function seedSuppliers(): void
    {
        foreach (self::SUPPLIERS as [$name, $phone, $address]) {
            $this->suppliers[$name] = Supplier::updateOrCreate(['name' => $name], [
                'phone' => $phone,
                'address' => $address,
                'active' => true,
            ]);
        }
    }

    private function seedJobTypes(): void
    {
        foreach (self::JOB_TYPES as [$name, $description, $type, $mechanicShare, $workshopShare, $allowProducts]) {
            $isPercentage = $type === 'percentage';

            $this->jobTypes[$name] = JobType::updateOrCreate(['name' => $name], [
                'description' => $description,
                'default_description' => $description,
                'calculation_type' => $type,
                'mechanic_percentage' => $isPercentage ? $mechanicShare : null,
                'workshop_percentage' => $isPercentage ? $workshopShare : null,
                'fixed_mechanic_amount' => $isPercentage ? null : $mechanicShare,
                'fixed_workshop_amount' => $isPercentage ? null : $workshopShare,
                'allow_products' => $allowProducts,
                'allow_custom_labor' => $isPercentage,
                'is_active' => true,
            ]);
        }
    }

    private function seedMechanics(Branch $branch): void
    {
        foreach (self::MECHANICS[$branch->name] as [$name, $phone, $months]) {
            $this->mechanics[$branch->id][] = Mechanic::updateOrCreate(['name' => $name, 'branch_id' => $branch->id], [
                'phone' => $phone,
                'hire_date' => now()->subMonths($months)->toDateString(),
                'is_active' => true,
            ]);
        }
    }

    /**
     * Crea el catálogo con dos lotes de distinto costo por producto, para mostrar el FIFO.
     */
    private function seedCatalog(Branch $branch, CarbonImmutable $firstDay, bool $isMain): void
    {
        foreach (self::CATALOG as [$name, $category, $cost, $price, $minStock, $supplierNames, $qtyMain, $qtyCentro, $upc]) {
            $this->travelTo($firstDay->subDays(20), 9);

            $product = Product::create([
                'name' => $name,
                'category' => $category,
                'branch_id' => $branch->id,
                'purchase_price' => $cost,
                'sale_price' => $price,
                'stock' => 0,
                'min_stock' => $minStock,
                'upc' => $upc,
            ]);
            $product->suppliers()->sync(collect($supplierNames)->map(fn (string $supplier) => $this->suppliers[$supplier]->id));

            $this->products[$branch->id][$name] = $product;

            $quantity = $isMain ? $qtyMain : $qtyCentro;
            $olderBatch = intdiv($quantity, 2);

            if ($olderBatch > 0) {
                $this->purchase($product, $olderBatch, $this->roundTo($cost * 0.94, 100), $this->roundTo($price * 0.95, 500), $firstDay->subDays(20), 'Carga inicial');
            }

            if ($quantity - $olderBatch > 0) {
                $this->purchase($product, $quantity - $olderBatch, $cost, $price, $firstDay->subDays(3), 'Compra FAC-PROV-' . mt_rand(1000, 9999));
            }
        }
    }

    private function simulateDay(
        CarbonImmutable $date,
        int $dayIndex,
        int $daysAgo,
        Branch $branch,
        User $cashier,
        bool $isMain,
        int $lastMinute
    ): void {
        $isRestockDay = in_array($date->dayOfWeek, [Carbon::MONDAY, Carbon::THURSDAY], true);

        if ($isRestockDay && $daysAgo > self::DAYS_WITHOUT_RESTOCK) {
            $this->restock($branch, $date, $dayIndex, $isMain);
        }

        $events = [];

        for ($i = 0, $count = $isMain ? mt_rand(2, 5) : mt_rand(1, 3); $i < $count; $i++) {
            $events[] = ['sale', mt_rand(8 * 60, 18 * 60)];
        }

        for ($i = 0, $count = $isMain ? mt_rand(1, 3) : mt_rand(0, 2); $i < $count; $i++) {
            $events[] = ['order', mt_rand(8 * 60, 15 * 60)];
        }

        usort($events, fn (array $a, array $b) => $a[1] <=> $b[1]);

        foreach ($events as [$type, $minute]) {
            if ($minute > $lastMinute) {
                continue;
            }

            $this->travelTo($date, intdiv($minute, 60), $minute % 60);

            if ($type === 'sale') {
                $this->counterSale($branch, $cashier);
            } else {
                $this->serviceOrder($branch, $date, $daysAgo, $minute);
            }
        }
    }

    private function counterSale(Branch $branch, User $cashier): void
    {
        $items = [];
        $lines = (int) $this->weightedPick([1 => 60, 2 => 30, 3 => 10]);

        for ($line = 0; $line < $lines; $line++) {
            $name = (string) $this->weightedPick(self::COUNTER_ITEMS);
            $product = $this->products[$branch->id][$name] ?? null;
            $quantity = str_starts_with($name, 'Aceite') ? mt_rand(1, 2) : 1;

            if (!$product || isset($items[$product->id]) || $product->fresh()->stock < $quantity) {
                continue;
            }

            $items[$product->id] = ['id' => $product->id, 'quantity' => $quantity];
        }

        if (empty($items)) {
            return;
        }

        $customer = mt_rand(1, 100) <= 55 ? null : $this->pick(self::CUSTOMERS)[0];

        try {
            $result = $this->sales->create(
                $branch->id,
                $cashier->id,
                $customer,
                (string) $this->weightedPick(self::PAYMENT_METHODS),
                array_values($items)
            );
            $this->stats['sales']++;

            // Algunas ventas se anulan por error de digitación.
            if (mt_rand(1, 100) <= 3) {
                Carbon::setTestNow(now()->addMinutes(mt_rand(5, 40)));
                $this->sales->cancel($result['sale']);
                $this->stats['cancelled_sales']++;
            }
        } catch (InsufficientStockException|BusinessRuleException) {
            // Sin stock suficiente: el cliente se fue sin comprar.
        }
    }

    private function serviceOrder(Branch $branch, CarbonImmutable $date, int $daysAgo, int $minute): void
    {
        [$customer, $phone, $vehicle] = $this->pick(self::CUSTOMERS);

        $order = ServiceOrder::create([
            'branch_id' => $branch->id,
            'customer_name' => $customer,
            'customer_phone' => $phone,
            'vehicle_info' => $vehicle,
            'service_description' => $this->pick(self::COMPLAINTS),
            'status' => ServiceOrder::STATUS_PENDING,
        ]);
        $this->stats['orders']++;

        // Motos que acaban de llegar y siguen esperando diagnóstico.
        if ($daysAgo <= 1 && mt_rand(1, 100) <= 35) {
            return;
        }

        // Clientes que no autorizaron el trabajo.
        if ($daysAgo > 1 && mt_rand(1, 100) <= 5) {
            $order->update(['status' => ServiceOrder::STATUS_CANCELLED]);

            return;
        }

        $jobs = [];
        $jobCount = mt_rand(1, 100) <= 70 ? 1 : 2;

        for ($i = 1; $i <= $jobCount; $i++) {
            $jobMinute = $minute + 20 * $i;
            $this->travelTo($date, intdiv($jobMinute, 60), $jobMinute % 60);

            $jobType = $this->jobTypes[$this->weightedPick($this->jobTypeWeights())];

            try {
                $jobs[] = $this->jobs->addToOrder($order, [
                    'job_type_id' => $jobType->id,
                    'mechanic_id' => $this->pick($this->mechanics[$branch->id])->id,
                    'labor_cost' => $this->laborFor($jobType->name),
                    'description' => $jobType->description,
                ], $this->partsFor($jobType->name, $branch));
            } catch (InsufficientStockException|BusinessRuleException) {
                // Sin repuestos: el trabajo no se registra.
            }
        }

        if (empty($jobs)) {
            return;
        }

        // Trabajos de hoy y ayer siguen en el taller.
        if ($daysAgo <= 1) {
            if (mt_rand(1, 100) <= 40) {
                $jobs[0]->update(['status' => WorkshopJob::STATUS_COMPLETED, 'completed_at' => now()->addHours(2)]);
            }

            return;
        }

        $finishMinute = $minute + mt_rand(120, 300);
        $this->travelTo($date, intdiv($finishMinute, 60), $finishMinute % 60);

        foreach ($jobs as $job) {
            $job->update(['status' => WorkshopJob::STATUS_COMPLETED, 'completed_at' => now()]);
        }

        // Algunas motos terminadas aún no se han entregado ni facturado.
        if ($daysAgo <= 4 && mt_rand(1, 100) <= 40) {
            return;
        }

        try {
            $this->invoices->generateFor($order);
            $this->stats['invoices']++;
        } catch (BusinessRuleException) {
        }
    }

    private function restock(Branch $branch, CarbonImmutable $date, int $dayIndex, bool $isMain): void
    {
        // El segundo mes llega con precios de proveedor más altos: los lotes conviven con costos distintos.
        $costIncrease = $dayIndex >= 30 ? 1.04 : 1.0;
        $priceIncrease = $dayIndex >= 30 ? 1.05 : 1.0;

        foreach (self::CATALOG as [$name, , $cost, $price, $minStock, , $qtyMain, $qtyCentro]) {
            $product = $this->products[$branch->id][$name]->fresh();
            $target = $isMain ? $qtyMain : $qtyCentro;

            if ($target === 0 || $product->stock > $minStock) {
                continue;
            }

            $this->purchase(
                $product,
                max($target - $product->stock, $minStock),
                $this->roundTo($cost * $costIncrease, 100),
                $this->roundTo($price * $priceIncrease, 500),
                $date,
                'Compra FAC-PROV-' . mt_rand(1000, 9999)
            );
        }
    }

    private function branchEvents(CarbonImmutable $date, int $dayIndex, int $daysAgo, Branch $principal, Branch $centro, User $admin): void
    {
        if ($dayIndex === 20) {
            $this->transfer($principal, $centro, $admin, 'Bujía NGK CR7HSA', 10, $date, BranchTransfer::STATUS_COMPLETED);
        }

        if ($dayIndex === 38) {
            $this->adjust($principal, 'Bombillo H4 12V 35/35W', -2, 'damage', 'Bombillos rotos al descargar el pedido', $date);
        }

        if ($dayIndex === 52) {
            $this->adjust($centro, 'Guaya de clutch Boxer CT100', -1, 'loss', 'Faltante en conteo físico mensual', $date);
        }

        if ($daysAgo === 1) {
            $this->transfer($principal, $centro, $admin, 'Filtro de aceite Pulsar 180/200', 4, $date, BranchTransfer::STATUS_PENDING);
            $this->transfer($principal, $centro, $admin, 'Pastillas de freno delanteras FZ', 2, $date, BranchTransfer::STATUS_IN_TRANSIT);
        }
    }

    private function transfer(Branch $from, Branch $to, User $admin, string $productName, int $quantity, CarbonImmutable $date, string $finalStatus): void
    {
        $product = $this->products[$from->id][$productName]->fresh();

        if ($product->stock < $quantity) {
            return;
        }

        $this->travelTo($date, 10, 15);

        $transfer = BranchTransfer::create([
            'from_branch_id' => $from->id,
            'to_branch_id' => $to->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $product->purchase_price,
            'status' => BranchTransfer::STATUS_PENDING,
            'requested_by' => $admin->id,
            'reference' => "Reposición {$to->name}",
        ]);

        if ($finalStatus === BranchTransfer::STATUS_PENDING) {
            return;
        }

        $transfer->update(['status' => BranchTransfer::STATUS_IN_TRANSIT]);

        if ($finalStatus === BranchTransfer::STATUS_IN_TRANSIT) {
            return;
        }

        $this->travelTo($date, 16, 40);

        try {
            $this->transfers->complete($transfer, $admin->id);
        } catch (InsufficientStockException|BusinessRuleException) {
        }
    }

    private function adjust(Branch $branch, string $productName, int $quantity, string $reason, string $notes, CarbonImmutable $date): void
    {
        $this->travelTo($date, 17, 30);

        try {
            $this->inventory->adjustStock($this->products[$branch->id][$productName]->id, $quantity, $reason, $notes);
        } catch (InsufficientStockException) {
        }
    }

    private function purchase(Product $product, int $quantity, int $cost, int $price, CarbonImmutable $date, string $reference): void
    {
        $this->travelTo($date, mt_rand(8, 11), mt_rand(0, 59));

        $this->inventory->registerPurchaseBatch([
            'product_id' => $product->id,
            'supplier_id' => $product->suppliers()->value('suppliers.id'),
            'quantity' => $quantity,
            'cost_price' => $cost,
            'sale_price' => $price,
            'reference' => $reference,
            'purchased_at' => now(),
        ]);
    }

    /**
     * @return array<int, array{id: int, quantity: int}>
     */
    private function partsFor(string $jobTypeName, Branch $branch): array
    {
        $parts = [];

        foreach (self::JOB_PARTS[$jobTypeName] ?? [] as [$name, $probability, $quantity]) {
            if (mt_rand(1, 100) > $probability) {
                continue;
            }

            if (isset(self::PART_GROUPS[$name])) {
                $name = $this->pick(self::PART_GROUPS[$name]);
            }

            $product = $this->products[$branch->id][$name] ?? null;

            if (!$product || isset($parts[$product->id]) || $product->fresh()->stock < $quantity) {
                continue;
            }

            $parts[$product->id] = ['id' => $product->id, 'quantity' => $quantity];
        }

        return array_values($parts);
    }

    private function laborFor(string $jobTypeName): int
    {
        foreach (self::JOB_TYPES as [$name, , $type, $mechanicShare, $workshopShare, , $min, $max]) {
            if ($name === $jobTypeName) {
                return $type === 'fixed'
                    ? $mechanicShare + $workshopShare
                    : $this->roundTo(mt_rand($min, $max), 5000);
            }
        }

        return 0;
    }

    /**
     * @return array<string, int>
     */
    private function jobTypeWeights(): array
    {
        $weights = [];

        foreach (self::JOB_TYPES as $jobType) {
            $weights[$jobType[0]] = $jobType[8];
        }

        return $weights;
    }

    private function travelTo(CarbonImmutable $date, int $hour, int $minute = 0): void
    {
        Carbon::setTestNow($date->setTime($hour, $minute));
    }

    /**
     * @param  array<string|int, int>  $weights
     */
    private function weightedPick(array $weights): string|int
    {
        $roll = mt_rand(1, array_sum($weights));

        foreach ($weights as $option => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $option;
            }
        }

        return array_key_first($weights);
    }

    /**
     * @template T
     *
     * @param  array<int, T>  $options
     * @return T
     */
    private function pick(array $options): mixed
    {
        return $options[mt_rand(0, count($options) - 1)];
    }

    private function roundTo(float|int $value, int $step): int
    {
        return (int) (round($value / $step) * $step);
    }

    private function printSummary(): void
    {
        if (!$this->command) {
            return;
        }

        $this->command->info('Datos de demostración generados.');
        $this->command->table(['Rol', 'Correo', 'Contraseña', 'Sucursales'], [
            ['Administrador', 'admin@elflaco.test', self::DEMO_PASSWORD, 'Todas'],
            ['Cajera', 'caja.principal@elflaco.test', self::DEMO_PASSWORD, 'Sede Principal'],
            ['Cajero', 'caja.centro@elflaco.test', self::DEMO_PASSWORD, 'Sede Centro'],
            ['Jefe de taller', 'jefe.taller@elflaco.test', self::DEMO_PASSWORD, 'Ambas sedes'],
            ['Inactivo (no puede entrar)', 'exempleado@elflaco.test', self::DEMO_PASSWORD, '-'],
        ]);
        $this->command->line(sprintf(
            'Ventas: %d (%d anuladas) · Órdenes: %d · Facturas: %d',
            $this->stats['sales'],
            $this->stats['cancelled_sales'],
            $this->stats['orders'],
            $this->stats['invoices']
        ));
    }
}
