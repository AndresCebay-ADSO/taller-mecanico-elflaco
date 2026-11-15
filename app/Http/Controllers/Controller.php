<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Sale;
use App\Services\BranchService;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function currentBranchId(): ?int
    {
        return app(BranchService::class)->getCurrentBranchId();
    }

    /**
     * Sucursal activa obligatoria para operaciones que crean registros.
     */
    protected function requireCurrentBranchId(): int
    {
        $branchId = $this->currentBranchId();

        abort_if($branchId === null, 403, 'No tienes una sucursal activa asignada. Contacta al administrador.');

        return $branchId;
    }

    /**
     * Responde 404 si el registro pertenece a una sucursal distinta de la activa.
     */
    protected function ensureCurrentBranch(?int $branchId): void
    {
        abort_if($branchId === null || $branchId !== $this->currentBranchId(), 404);
    }

    /**
     * Respuesta a «¿Le devolviste la plata al cliente?» al anular efectivo de un día anterior (decisión 2).
     * En los demás casos no se pregunta y es false.
     */
    protected function refundAnswer(Request $request, Sale|Invoice $document): bool
    {
        if (!$document->asksForRefund()) {
            return false;
        }

        $request->validate(['refunded' => 'required|boolean'], [
            'refunded.required' => 'Indica si le devolviste la plata al cliente.',
        ]);

        return $request->boolean('refunded');
    }
}
