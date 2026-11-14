<?php

namespace App\Console\Commands;

use App\Services\InventoryService;
use Illuminate\Console\Command;

class CheckInventoryConsistency extends Command
{
    protected $signature = 'inventory:check {--fix : Ajusta los lotes para que coincidan con el stock registrado}';

    protected $description = 'Verifica que el stock de cada producto coincida con la suma de sus lotes FIFO';

    public function handle(InventoryService $inventoryService): int
    {
        $discrepancies = $inventoryService->findBatchDiscrepancies();

        if ($discrepancies->isEmpty()) {
            $this->info('Inventario consistente: el stock de todos los productos coincide con sus lotes.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Producto', 'Sucursal', 'Stock', 'Lotes', 'Diferencia'],
            $discrepancies->map(fn ($product) => [
                $product->id,
                $product->name,
                $product->branch_id ?? '-',
                $product->stock,
                $product->batch_stock,
                $product->stock - $product->batch_stock,
            ])->all()
        );

        if (!$this->option('fix')) {
            $this->warn("{$discrepancies->count()} producto(s) con diferencias. Ejecuta con --fix para conciliar los lotes.");

            return self::FAILURE;
        }

        foreach ($discrepancies as $product) {
            $inventoryService->reconcileBatches($product->id);
        }

        $this->info("Lotes conciliados para {$discrepancies->count()} producto(s).");

        return self::SUCCESS;
    }
}
