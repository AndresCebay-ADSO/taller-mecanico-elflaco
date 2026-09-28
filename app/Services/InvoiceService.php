<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * Genera la factura consolidada de una orden (mano de obra + repuestos) y la marca como completada.
     *
     * @throws BusinessRuleException
     */
    public function generateFor(ServiceOrder $serviceOrder): Invoice
    {
        return DB::transaction(function () use ($serviceOrder) {
            $order = ServiceOrder::whereKey($serviceOrder->id)->lockForUpdate()->firstOrFail();

            if ($order->invoices()->exists()) {
                throw new BusinessRuleException('Esta orden ya tiene una factura vinculada.', 'invoice');
            }

            if (!in_array($order->status, [ServiceOrder::STATUS_IN_PROGRESS, ServiceOrder::STATUS_COMPLETED], true)) {
                throw new BusinessRuleException('Solo se pueden facturar ordenes en progreso o completadas.', 'invoice');
            }

            $jobs = $order->workshopJobs()
                ->where('status', '!=', WorkshopJob::STATUS_CANCELLED)
                ->with('jobProducts')
                ->get();

            if ($jobs->isEmpty()) {
                throw new BusinessRuleException('La orden no tiene trabajos para facturar.', 'invoice');
            }

            if ($jobs->contains(fn (WorkshopJob $job) => $job->status !== WorkshopJob::STATUS_COMPLETED)) {
                throw new BusinessRuleException('Completa todos los trabajos antes de generar la factura.', 'invoice');
            }

            $amount = $jobs->sum('total_amount') + $jobs->sum(fn (WorkshopJob $job) => $job->jobProducts->sum('total_price'));

            $invoice = Invoice::create([
                'invoice_number' => 'TMP-' . Str::uuid(),
                'service_order_id' => $order->id,
                'amount' => $amount,
                'invoice_date' => now(),
            ]);

            // Numeracion consecutiva basada en el id: unica y sin condiciones de carrera.
            $invoice->update(['invoice_number' => sprintf('FAC-%06d', $invoice->id)]);

            if ($order->status !== ServiceOrder::STATUS_COMPLETED) {
                $order->update([
                    'status' => ServiceOrder::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);
            }

            return $invoice;
        }, 3);
    }
}
