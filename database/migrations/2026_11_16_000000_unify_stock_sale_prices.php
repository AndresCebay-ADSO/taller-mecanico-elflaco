<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Un solo precio de venta por producto (decisión 4 de docs/AUDITORIA_DECISIONES.md).
 * Las unidades que quedan en inventario pasan al precio actual del producto.
 * El costo de cada lote no se toca.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('batches')
            ->where('remaining_stock', '>', 0)
            ->update([
                'sale_price' => DB::raw('(SELECT products.sale_price FROM products WHERE products.id = batches.product_id)'),
            ]);
    }

    public function down(): void
    {
        // Los precios anteriores de cada lote no se guardan: no hay vuelta atrás.
    }
};
