<?php

namespace App\Services\DataImport;

use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\InventoryService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use Throwable;

/**
 * Importa la plantilla de carga inicial (proveedores, productos con su stock, mecánicos y tipos de trabajo).
 *
 * Dos pasos: analyze() revisa todo sin escribir nada y devuelve qué se va a crear, qué se omite y los
 * errores fila por fila; import() vuelve a revisar y, solo si no hay errores, crea todo en una transacción.
 * Lo que ya existe con el mismo nombre se omite (no se duplica ni se modifica).
 */
class WorkbookImporter
{
    /** Filas con datos por hoja: la carga inicial de un taller pequeño no llega ni cerca. */
    private const MAX_ROWS = 5000;

    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function analyze(string $path, int $branchId): ImportReport
    {
        $report = new ImportReport();
        $sheets = $this->read($path, $report);

        $fileSuppliers = $this->analyzeSuppliers($sheets[ImportSchema::SUPPLIERS] ?? [], $report);
        $this->analyzeProducts($sheets[ImportSchema::PRODUCTS] ?? [], $branchId, $fileSuppliers, $report);
        $this->analyzeMechanics($sheets[ImportSchema::MECHANICS] ?? [], $branchId, $report);
        $this->analyzeJobTypes($sheets[ImportSchema::JOB_TYPES] ?? [], $report);

        return $report;
    }

    public function import(string $path, int $branchId): ImportReport
    {
        $report = $this->analyze($path, $branchId);

        if ($report->hasErrors()) {
            return $report;
        }

        DB::transaction(function () use ($report, $branchId) {
            $supplierIds = [];
            foreach ($report->create[ImportSchema::SUPPLIERS] ?? [] as $data) {
                $supplier = Supplier::create([...$data, 'active' => true]);
                $supplierIds[$this->key($supplier->name)] = $supplier->id;
            }

            foreach ($report->create[ImportSchema::PRODUCTS] ?? [] as $data) {
                $supplierId = $supplierIds[$this->key($data['supplier'])]
                    ?? Supplier::whereRaw('LOWER(name) = ?', [$this->key($data['supplier'])])->value('id');

                // Igual que al crear un producto a mano: nace en 0 y el stock entra como primer lote.
                $product = Product::create([
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'upc' => $data['upc'],
                    'purchase_price' => $data['cost'],
                    'sale_price' => $data['price'],
                    'stock' => 0,
                    'min_stock' => $data['min_stock'],
                    'branch_id' => $branchId,
                ]);
                $product->suppliers()->sync([$supplierId]);

                if ($data['stock'] > 0) {
                    $this->inventory->registerPurchaseBatch([
                        'product_id' => $product->id,
                        'supplier_id' => $supplierId,
                        'quantity' => $data['stock'],
                        'cost_price' => $data['cost'],
                        'sale_price' => $data['price'],
                        'reference' => 'Carga inicial (importación)',
                        'purchased_at' => now(),
                    ]);
                }
            }

            foreach ($report->create[ImportSchema::MECHANICS] ?? [] as $data) {
                Mechanic::create([...$data, 'branch_id' => $branchId, 'is_active' => true]);
            }

            foreach ($report->create[ImportSchema::JOB_TYPES] ?? [] as $data) {
                JobType::create([...$data, 'is_active' => true]);
            }
        });

        return $report;
    }

    // ── Lectura ──────────────────────────────────────────────────────────────────

    /**
     * @return array<string, list<array{row: int, data: array<string, mixed>}>> hoja => filas con datos
     */
    private function read(string $path, ImportReport $report): array
    {
        $reader = new Reader();

        try {
            $reader->open($path);
        } catch (Throwable) {
            throw new RuntimeException('No se pudo abrir el archivo. Debe ser la plantilla en formato Excel (.xlsx).');
        }

        $sheets = [];
        $wanted = collect(ImportSchema::SHEETS)->keys()->mapWithKeys(fn ($name) => [$this->key($name) => $name]);

        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetName = $wanted[$this->key($sheet->getName())] ?? null;
            if ($sheetName === null) {
                continue; // Instrucciones, Categorías, Listas…
            }

            $columns = null;
            foreach ($sheet->getRowIterator() as $rowNumber => $row) {
                $values = $row->toArray();

                if ($columns === null) {
                    $columns = $this->mapHeader($sheetName, $values, $report);
                    if ($columns === null) {
                        break;
                    }
                    continue;
                }

                $data = [];
                foreach ($columns as $index => $field) {
                    $data[$field] = $this->clean($values[$index] ?? null);
                }

                // La plantilla trae 500 filas con formato: solo cuentan las que tienen algo escrito.
                if (collect($data)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty()) {
                    $sheets[$sheetName][] = ['row' => $rowNumber, 'data' => $data];

                    // Un archivo pequeño puede traer cientos de miles de filas comprimidas: se corta antes de agotar la memoria.
                    if (count($sheets[$sheetName]) > self::MAX_ROWS) {
                        $reader->close();
                        throw new RuntimeException("La hoja «{$sheetName}» tiene más de " . number_format(self::MAX_ROWS, 0, ',', '.') . ' filas. Divide la carga en varios archivos.');
                    }
                }
            }
        }

        $reader->close();

        return $sheets;
    }

    /** @return array<int, string>|null índice de columna => clave interna */
    private function mapHeader(string $sheet, array $header, ImportReport $report): ?array
    {
        $positions = [];
        foreach ($header as $index => $title) {
            $positions[$this->key(str_replace('*', '', (string) $title))] = $index;
        }

        $columns = [];
        foreach (ImportSchema::SHEETS[$sheet] as $field => $title) {
            if (! isset($positions[$this->key($title)])) {
                $report->error($sheet, 1, "Falta la columna «{$title}». Use la plantilla sin cambiar los encabezados.");

                return null;
            }
            $columns[$positions[$this->key($title)]] = $field;
        }

        return $columns;
    }

    // ── Revisión por hoja ────────────────────────────────────────────────────────

    /** @return array<string, true> nombres (normalizados) de proveedores del archivo */
    private function analyzeSuppliers(array $rows, ImportReport $report): array
    {
        $sheet = ImportSchema::SUPPLIERS;
        $existing = Supplier::pluck('name')->mapWithKeys(fn ($n) => [$this->key($n) => true])->all();
        $seen = [];

        foreach ($rows as ['row' => $row, 'data' => $data]) {
            $data['phone'] = $this->digits($data['phone']);

            if (! $this->validate($sheet, $row, $data, [
                'name' => 'required|string|max:30',
                'phone' => 'required|digits:10',
                'email' => 'nullable|email|max:255',
                'address' => 'nullable|string|max:50',
            ], $report)) {
                continue;
            }

            $key = $this->key($data['name']);
            if (isset($seen[$key])) {
                $report->error($sheet, $row, "«{$data['name']}» está repetido en el archivo (fila {$seen[$key]}).");
                continue;
            }
            $seen[$key] = $row;

            isset($existing[$key])
                ? $report->skip[$sheet][] = $data['name']
                : $report->create[$sheet][] = $data;
        }

        return array_map(fn () => true, $seen);
    }

    private function analyzeProducts(array $rows, int $branchId, array $fileSuppliers, ImportReport $report): void
    {
        $sheet = ImportSchema::PRODUCTS;
        $existing = Product::withTrashed()->where('branch_id', $branchId)->pluck('name')
            ->mapWithKeys(fn ($n) => [$this->key($n) => true])->all();
        $knownSuppliers = $fileSuppliers + Supplier::where('active', true)->pluck('name')
            ->mapWithKeys(fn ($n) => [$this->key($n) => true])->all();
        $seen = [];

        foreach ($rows as ['row' => $row, 'data' => $data]) {
            $data['cost'] = $this->money($data['cost']);
            $data['price'] = $this->money($data['price']);
            $data['stock'] = $this->integer($data['stock'], 0);
            $data['min_stock'] = $this->integer($data['min_stock'], 0);
            // Un código de barras escrito en una celda numérica llega como 7.702001000141E+12.
            $data['upc'] = match (true) {
                $data['upc'] === null => null,
                is_int($data['upc']) || is_float($data['upc']) => sprintf('%.0f', $data['upc']),
                default => (string) $data['upc'],
            };

            if (! $this->validate($sheet, $row, $data, [
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:255',
                'upc' => 'nullable|string|max:50',
                'supplier' => 'required|string',
                'cost' => 'required|numeric|min:0|max:99999999',
                'price' => 'required|numeric|min:0|max:99999999',
                'stock' => 'required|integer|min:0|max:100000',
                'min_stock' => 'required|integer|min:0|max:100000',
            ], $report)) {
                continue;
            }

            if (! isset($knownSuppliers[$this->key($data['supplier'])])) {
                $report->error($sheet, $row, "El proveedor «{$data['supplier']}» no está en la hoja Proveedores ni en el sistema.");
                continue;
            }

            $key = $this->key($data['name']);
            if (isset($seen[$key])) {
                $report->error($sheet, $row, "«{$data['name']}» está repetido en el archivo (fila {$seen[$key]}).");
                continue;
            }
            $seen[$key] = $row;

            if (isset($existing[$key])) {
                $report->skip[$sheet][] = $data['name'];
                continue;
            }

            if ($data['price'] < $data['cost']) {
                $report->warning($sheet, $row, "«{$data['name']}» se vende por debajo de su costo.");
            }

            $report->create[$sheet][] = $data;
        }
    }

    private function analyzeMechanics(array $rows, int $branchId, ImportReport $report): void
    {
        $sheet = ImportSchema::MECHANICS;
        $existing = Mechanic::where('branch_id', $branchId)->pluck('name')
            ->mapWithKeys(fn ($n) => [$this->key($n) => true])->all();
        $seen = [];
        $seenEmails = [];

        foreach ($rows as ['row' => $row, 'data' => $data]) {
            $data['phone'] = $this->digits($data['phone']);
            $data['hire_date'] = $this->date($data['hire_date']) ?? now()->toDateString();

            if (! $this->validate($sheet, $row, $data, [
                'name' => 'required|string|max:30',
                'phone' => 'required|digits:10',
                'hire_date' => 'required|date',
                'email' => 'nullable|email|max:255|unique:mechanics,email',
            ], $report)) {
                continue;
            }

            $key = $this->key($data['name']);
            if (isset($seen[$key])) {
                $report->error($sheet, $row, "«{$data['name']}» está repetido en el archivo (fila {$seen[$key]}).");
                continue;
            }
            $seen[$key] = $row;

            // El correo es único: dos filas con el mismo fallarían al guardar.
            $email = $this->key((string) ($data['email'] ?? ''));
            if ($email !== '' && isset($seenEmails[$email])) {
                $report->error($sheet, $row, "El correo «{$data['email']}» está repetido en el archivo (fila {$seenEmails[$email]}).");
                continue;
            }
            if ($email !== '') {
                $seenEmails[$email] = $row;
            }

            isset($existing[$key])
                ? $report->skip[$sheet][] = $data['name']
                : $report->create[$sheet][] = $data;
        }
    }

    private function analyzeJobTypes(array $rows, ImportReport $report): void
    {
        $sheet = ImportSchema::JOB_TYPES;
        $existing = JobType::pluck('name')->mapWithKeys(fn ($n) => [$this->key($n) => true])->all();
        $seen = [];

        foreach ($rows as ['row' => $row, 'data' => $data]) {
            $calculation = match ($this->key((string) $data['calculation'])) {
                'porcentaje' => 'percentage',
                'monto fijo', 'fijo' => 'fixed',
                default => null,
            };

            $percentage = $calculation === 'percentage' ? $this->money($data['mechanic_percentage']) : null;
            // Una celda con formato % llega como fracción (60 % → 0,6): nadie le da menos de 1 % a un mecánico.
            if ((is_int($percentage) || is_float($percentage)) && $percentage > 0 && $percentage < 1) {
                $percentage = round($percentage * 100, 2);
            }

            $clean = [
                'name' => $data['name'],
                'description' => $data['description'],
                'calculation_type' => $calculation,
                'mechanic_percentage' => $percentage,
                'fixed_mechanic_amount' => $calculation === 'fixed' ? $this->money($data['fixed_mechanic']) : null,
                'fixed_workshop_amount' => $calculation === 'fixed' ? $this->money($data['fixed_workshop']) : null,
                'allow_products' => $this->yesNo($data['allow_products']),
            ];

            if (! $this->validate($sheet, $row, $clean, [
                'name' => 'required|string|max:255',
                'description' => 'required|string|max:255',
                'calculation_type' => 'required|in:percentage,fixed',
                'mechanic_percentage' => 'nullable|required_if:calculation_type,percentage|numeric|min:0|max:100',
                'fixed_mechanic_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0|max:99999999',
                'fixed_workshop_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0|max:99999999',
                'allow_products' => 'required|boolean',
            ], $report, ['calculation_type' => 'Cálculo', 'mechanic_percentage' => '% mecánico', 'fixed_mechanic_amount' => 'Monto mecánico', 'fixed_workshop_amount' => 'Monto taller', 'allow_products' => '¿Usa repuestos?'])) {
                continue;
            }

            $key = $this->key($clean['name']);
            if (isset($seen[$key])) {
                $report->error($sheet, $row, "«{$clean['name']}» está repetido en el archivo (fila {$seen[$key]}).");
                continue;
            }
            $seen[$key] = $row;

            if (isset($existing[$key])) {
                $report->skip[$sheet][] = $clean['name'];
                continue;
            }

            $clean['workshop_percentage'] = $calculation === 'percentage' ? 100 - $clean['mechanic_percentage'] : null;
            $report->create[$sheet][] = $clean;
        }
    }

    // ── Utilidades ───────────────────────────────────────────────────────────────

    /** Valida una fila con las reglas de los formularios; los errores se reportan con su fila. */
    private function validate(string $sheet, int $row, array $data, array $rules, ImportReport $report, array $names = []): bool
    {
        $titles = $names + ImportSchema::SHEETS[$sheet];
        $validator = Validator::make($data, $rules, [], $titles);

        foreach ($validator->errors()->all() as $message) {
            $report->error($sheet, $row, $message);
        }

        return ! $validator->fails();
    }

    /** Clave para comparar nombres: sin mayúsculas ni espacios de más. */
    private function key(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
    }

    private function clean(mixed $value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        return $value;
    }

    /** «$17.000», «17.000», «17000» y 17000 son lo mismo. Coma = decimales. */
    private function money(mixed $value): mixed
    {
        if ($value === null || is_int($value) || is_float($value)) {
            return $value;
        }

        $text = str_replace(['$', ' ', '.', '%'], '', (string) $value);
        $text = str_replace(',', '.', $text);

        return is_numeric($text) ? (float) $text : $value;
    }

    private function integer(mixed $value, int $default): mixed
    {
        if ($value === null) {
            return $default;
        }
        $number = $this->money($value);

        return is_numeric($number) && floor((float) $number) == $number ? (int) $number : $value;
    }

    private function digits(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_float($value) || is_int($value)
            ? (string) (int) $value
            : preg_replace('/[\s\.\-()]/', '', (string) $value);
    }

    private function date(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }
        if ($value === null) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $format) {
            try {
                return Carbon::createFromFormat('!' . $format, (string) $value)->toDateString();
            } catch (Throwable) {
                // probar el siguiente formato
            }
        }

        return (string) $value; // la validación «date» lo reporta
    }

    /** «Sí», «si», «x», «1» → true; «No», «n», «0» → false; vacío → true. */
    private function yesNo(mixed $value): mixed
    {
        if ($value === null) {
            return true;
        }

        return match ($this->key((string) $value)) {
            'sí', 'si', 's', 'x', '1', 'true', 'verdadero' => true,
            'no', 'n', '0', 'false', 'falso' => false,
            default => $value,
        };
    }
}
