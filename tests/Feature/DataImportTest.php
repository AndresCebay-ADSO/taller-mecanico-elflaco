<?php

use App\Models\Batch;
use App\Models\InventoryMovement;
use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\DataImport\ImportSchema;
use App\Services\DataImport\WorkbookImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Crea un .xlsx con las hojas de la plantilla. $rows: hoja => filas (sin encabezado); el encabezado se toma
 * del esquema, con «*» en las obligatorias como en la plantilla real.
 */
function importWorkbook(array $rows, array $headerOverrides = []): string
{
    $path = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
    $writer = new Writer();
    $writer->openToFile($path);
    $writer->getCurrentSheet()->setName('Instrucciones');
    $writer->addRow(Row::fromValues(['Plantilla de prueba']));

    foreach (ImportSchema::SHEETS as $sheet => $columns) {
        $writer->addNewSheetAndMakeItCurrent()->setName($sheet);
        $writer->addRow(Row::fromValues($headerOverrides[$sheet] ?? array_map(fn ($t) => $t . ' *', array_values($columns))));
        foreach ($rows[$sheet] ?? [] as $values) {
            $writer->addRow(Row::fromValues($values));
        }
        $writer->addRow(Row::fromValues(['', '', '']));
    }

    $writer->close();

    return $path;
}

function validWorkbook(): string
{
    return importWorkbook([
        'Proveedores' => [
            ['Distrimotos Neiva', '3101234567', 'ventas@distrimotos.co', 'Cra. 5 # 12-34'],
        ],
        'Productos' => [
            ['Filtro de aceite Pulsar 180', 'Filtros', '7702001000141', 'Distrimotos Neiva', '$9.400', '17.000', 12, 4],
            ['Bujía NGK CR7HSA', 'Encendido', '', 'distrimotos neiva', 9500, 17000, '', ''],
        ],
        'Mecánicos' => [
            ['Juan Pérez', '310 123 4567', '15/01/2024', ''],
        ],
        'Tipos de trabajo' => [
            ['Cambio de aceite', 'Cambio de aceite y revisión de niveles', 'Porcentaje', '60%', '', '', 'Sí', 'si'],
            ['Lavado', 'Lavado general', 'Monto fijo', '', 5000, 15000, 'No', 'No'],
        ],
    ]);
}

describe('importación desde Excel', function () {
    it('still reads an old template that has the removed «¿Mano de obra variable?» column', function () {
        [, $branch] = branchUser();
        $oldHeader = [...array_map(fn ($title) => $title . ' *', array_values(ImportSchema::SHEETS['Tipos de trabajo'])), '¿Mano de obra variable?'];
        $workbook = importWorkbook([
            'Tipos de trabajo' => [['Cambio de aceite', 'Cambio de aceite y revisión', 'Porcentaje', '60', '', '', 'Sí', 'No']],
        ], ['Tipos de trabajo' => $oldHeader]);

        expect(app(WorkbookImporter::class)->analyze($workbook, $branch->id)->errors)->toBe([]);

        app(WorkbookImporter::class)->import($workbook, $branch->id);
        expect(JobType::where('name', 'Cambio de aceite')->value('calculation_type'))->toBe('percentage');
    });

    it('imports suppliers, products with their stock as a first batch, mechanics and job types', function () {
        [, $branch] = branchUser();
        $importer = app(WorkbookImporter::class);

        $preview = $importer->analyze(validWorkbook(), $branch->id);
        expect($preview->errors)->toBe([])
            ->and($preview->countCreate())->toBe(6)
            ->and(Product::count())->toBe(0); // revisar no escribe nada

        $importer->import(validWorkbook(), $branch->id);

        $filter = Product::where('name', 'Filtro de aceite Pulsar 180')->first();
        $spark = Product::where('name', 'Bujía NGK CR7HSA')->first();

        expect(Supplier::where('name', 'Distrimotos Neiva')->exists())->toBeTrue()
            ->and($filter->branch_id)->toBe($branch->id)
            ->and((float) $filter->purchase_price)->toBe(9400.0)
            ->and((float) $filter->sale_price)->toBe(17000.0)
            ->and($filter->stock)->toBe(12)
            ->and($filter->min_stock)->toBe(4)
            ->and($filter->upc)->toBe('7702001000141')
            ->and(batchStock($filter))->toBe(12)
            ->and(InventoryMovement::where('product_id', $filter->id)->where('movement_type', 'purchase')->sum('quantity'))->toEqual(12)
            ->and($spark->stock)->toBe(0)
            ->and(Batch::where('product_id', $spark->id)->exists())->toBeFalse()
            ->and($spark->suppliers()->first()->name)->toBe('Distrimotos Neiva')
            ->and(Mechanic::where('name', 'Juan Pérez')->first()->phone)->toBe('3101234567')
            ->and(Mechanic::where('name', 'Juan Pérez')->first()->hire_date->toDateString())->toBe('2024-01-15');

        $percentage = JobType::where('name', 'Cambio de aceite')->first();
        $fixed = JobType::where('name', 'Lavado')->first();
        expect((float) $percentage->mechanic_percentage)->toBe(60.0)
            ->and((float) $percentage->workshop_percentage)->toBe(40.0)
            ->and($fixed->calculation_type)->toBe('fixed')
            ->and((bool) $fixed->allow_products)->toBeFalse();
    });

    it('reports every problem with its row and imports nothing', function () {
        [, $branch] = branchUser();
        $path = importWorkbook([
            'Proveedores' => [
                ['Distrimotos', '310123', '', ''],              // fila 2: teléfono corto
                ['Lubricantes', '3101234567', '', ''],
                ['lubricantes', '3209876543', '', ''],          // fila 4: repetido
            ],
            'Productos' => [
                ['Filtro', 'Filtros', '', 'Proveedor fantasma', 1000, 2000, 1, 0],  // fila 2: proveedor inexistente
                ['Llanta', 'Llantas', '', 'Lubricantes', '', 2000, 1, 0],           // fila 3: sin costo
                ['Aceite', 'Aceites', '', 'Lubricantes', 30000, 25000, 2, 0],       // fila 4: aviso, vende bajo costo
            ],
            'Tipos de trabajo' => [
                ['Revisión', 'General', 'A ojo', '', '', '', '', ''],               // fila 2: cálculo inválido
            ],
        ]);

        $report = app(WorkbookImporter::class)->import($path, $branch->id);
        $where = collect($report->errors)->map(fn ($e) => "{$e['sheet']}:{$e['row']}")->all();

        expect($where)->toContain('Proveedores:2', 'Proveedores:4', 'Productos:2', 'Productos:3', 'Tipos de trabajo:2')
            ->and(collect($report->warnings)->pluck('row')->all())->toBe([4])
            ->and(Supplier::count())->toBe(0)
            ->and(Product::count())->toBe(0);
    });

    it('skips what already exists instead of duplicating it', function () {
        [, $branch] = branchUser();
        $importer = app(WorkbookImporter::class);
        $importer->import(validWorkbook(), $branch->id);

        $second = $importer->import(validWorkbook(), $branch->id);

        expect($second->countCreate())->toBe(0)
            ->and($second->countSkip())->toBe(6)
            ->and(Product::count())->toBe(2)
            ->and(Product::where('name', 'Filtro de aceite Pulsar 180')->first()->stock)->toBe(12);
    });

    it('rejects a sheet whose headers were changed', function () {
        [, $branch] = branchUser();
        $path = importWorkbook(['Proveedores' => [['X', '3101234567', '', '']]], ['Proveedores' => ['Proveedor', 'Celular']]);

        $report = app(WorkbookImporter::class)->analyze($path, $branch->id);

        expect($report->errors[0]['message'])->toContain('Falta la columna «Nombre»');
    });

    it('lets administrators preview and confirm, and keeps employees out', function () {
        [$admin, $branch] = branchUser(admin: true);
        [$employee] = branchUser(branch: $branch);
        $upload = new UploadedFile(validWorkbook(), 'plantilla.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($employee)->withSession(['current_branch_id' => $branch->id])
            ->get(route('imports.index'))->assertForbidden();

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->get(route('imports.template'))->assertOk();

        $response = $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->post(route('imports.preview'), ['file' => $upload])
            ->assertOk()
            ->assertSee('El archivo está bien')
            ->assertSee('Filtro de aceite Pulsar 180');

        expect(Product::count())->toBe(0);
        $token = $response->viewData('token');

        $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])
            ->post(route('imports.confirm'), ['token' => $token])
            ->assertRedirect(route('imports.index'))
            ->assertSessionHas('success');

        expect(Product::where('branch_id', $branch->id)->count())->toBe(2);
    });

    it('ships a template whose headers match what the importer expects', function () {
        $reader = new Reader();
        $reader->open(ImportSchema::template());
        $headers = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $headers[$sheet->getName()] = array_map(fn ($t) => trim(str_replace('*', '', (string) $t)), $row->toArray());
                break;
            }
        }
        $reader->close();

        foreach (ImportSchema::SHEETS as $sheet => $columns) {
            expect($headers[$sheet] ?? null)->toBe(array_values($columns), "La hoja «{$sheet}» de la plantilla no coincide con ImportSchema");
        }
    });
});

it('reports out-of-range numbers in the workbook as row errors instead of failing', function () {
    [, $branch] = branchUser();
    $path = importWorkbook([
        'Proveedores' => [['Lubricantes', '3101234567', '', '']],
        'Productos' => [
            ['Aceite', 'Aceites', '', 'Lubricantes', 30000, '999999999999999', 2, 0], // fila 2: precio fuera de rango
            ['Filtro', 'Filtros', '', 'Lubricantes', 9000, 17000, 5000000, 0],        // fila 3: stock absurdo
        ],
        'Tipos de trabajo' => [
            ['Lavado', 'Lavado general', 'Monto fijo', '', '999999999999', 1000, 'No', 'No'], // fila 2
        ],
    ]);

    $report = app(WorkbookImporter::class)->import($path, $branch->id);
    $where = collect($report->errors)->map(fn ($e) => "{$e['sheet']}:{$e['row']}")->all();

    expect($where)->toContain('Productos:2', 'Productos:3', 'Tipos de trabajo:2')
        ->and(Supplier::count())->toBe(0)
        ->and(Product::count())->toBe(0);
});

it('rejects out-of-range prices and stock when creating a product or a job type', function () {
    [$admin, $branch] = branchUser(admin: true);
    $supplier = Supplier::create(['name' => 'Proveedor', 'phone' => '3001112233', 'active' => true]);
    $session = ['current_branch_id' => $branch->id];

    $this->actingAs($admin)->withSession($session)->post(route('products.store'), [
        'name' => 'Llanta', 'category' => 'Llantas', 'supplier_ids' => [$supplier->id],
        'sale_price' => '999999999999999', 'stock' => 5000000, 'min_stock' => 0,
        'purchase_price' => 1000, 'initial_supplier_id' => $supplier->id,
    ])->assertSessionHasErrors(['sale_price', 'stock']);

    $this->actingAs($admin)->withSession($session)->post(route('job-types.store'), [
        'name' => 'Lavado', 'description' => 'Lavado', 'calculation_type' => 'fixed',
        'fixed_mechanic_amount' => '999999999999', 'fixed_workshop_amount' => 1000, 'is_active' => 1,
    ])->assertSessionHasErrors('fixed_mechanic_amount');
});

it('clears import files that were reviewed but never confirmed', function () {
    [$admin, $branch] = branchUser(admin: true);
    Storage::fake('local');
    Storage::disk('local')->put('imports/viejo.xlsx', 'x');
    touch(Storage::disk('local')->path('imports/viejo.xlsx'), now()->subDays(2)->getTimestamp());
    Storage::disk('local')->put('imports/reciente.xlsx', 'x');

    $this->actingAs($admin)->withSession(['current_branch_id' => $branch->id])->get(route('imports.index'))->assertOk();

    expect(Storage::disk('local')->exists('imports/viejo.xlsx'))->toBeFalse()
        ->and(Storage::disk('local')->exists('imports/reciente.xlsx'))->toBeTrue();
});

it('stops reading a workbook with an absurd number of rows', function () {
    [, $branch] = branchUser();
    $rows = array_fill(0, 5001, ['Juan Pérez', '3101234567', '15/01/2024', '']);

    expect(fn () => app(WorkbookImporter::class)->analyze(importWorkbook(['Mecánicos' => $rows]), $branch->id))
        ->toThrow(RuntimeException::class, 'más de 5.000 filas');
});
