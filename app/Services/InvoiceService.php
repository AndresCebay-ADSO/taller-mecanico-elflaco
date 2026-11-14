<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Invoice;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use App\Support\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    /**
     * Genera la factura consolidada de una orden (mano de obra + repuestos) y la marca como completada.
     * `$paymentMethod`: cómo pagó el cliente (PaymentMethod::ALL).
     *
     * @throws BusinessRuleException
     */
    public function generateFor(ServiceOrder $serviceOrder, string $paymentMethod = PaymentMethod::CASH): Invoice
    {
        if (!in_array($paymentMethod, PaymentMethod::ALL, true)) {
            throw new BusinessRuleException('Elige un método de pago válido.', 'payment_method');
        }

        return DB::transaction(function () use ($serviceOrder, $paymentMethod) {
            $order = ServiceOrder::whereKey($serviceOrder->id)->lockForUpdate()->firstOrFail();

            if ($order->activeInvoice()->exists()) {
                throw new BusinessRuleException('Esta orden ya tiene una factura vinculada.', 'invoice');
            }

            if (!in_array($order->status, [ServiceOrder::STATUS_IN_PROGRESS, ServiceOrder::STATUS_COMPLETED], true)) {
                throw new BusinessRuleException('Solo se pueden facturar órdenes en progreso o completadas.', 'invoice');
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
                'payment_method' => $paymentMethod,
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

    /**
     * Anula una factura conservando el registro (numeración sin huecos). La orden puede facturarse de nuevo.
     * Devuelve false si ya estaba anulada.
     */
    public function cancel(Invoice $invoice, ?int $cancelledBy): bool
    {
        return DB::transaction(function () use ($invoice, $cancelledBy) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCancelled()) {
                return false;
            }

            $locked->forceFill([
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
            ])->save();

            return true;
        }, 3);
    }
}
