<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

/**
 * Anular una venta o una factura y, si se le devolvió la plata al cliente, registrar la devolución en la caja de hoy
 * (decisión 2). Las dos cosas van juntas: si la devolución no se puede registrar, tampoco se anula.
 */
class CancellationService
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly InvoiceService $invoices,
        private readonly CashService $cash,
    ) {
    }

    /**
     * `$refunded` solo cuenta en efectivo de un día anterior (asksForRefund()). Devuelve false si ya estaba anulada.
     *
     * @throws BusinessRuleException si hay que registrar la devolución y la caja de hoy está cerrada
     */
    public function cancel(Sale|Invoice $document, bool $refunded, ?int $userId): bool
    {
        $refunded = $refunded && $document->asksForRefund();

        return DB::transaction(function () use ($document, $refunded, $userId) {
            $cancelled = $document instanceof Sale
                ? $this->sales->cancel($document)
                : $this->invoices->cancel($document, $userId);

            if ($cancelled && $refunded) {
                $branchId = $document instanceof Sale ? $document->branch_id : $document->serviceOrder->branch_id;
                $this->cash->recordRefund($branchId, $document, $userId);
            }

            return $cancelled;
        });
    }
}
