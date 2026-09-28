<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\InsufficientStockException;
use App\Models\JobType;
use App\Models\Mechanic;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\WorkshopJob;
use App\Services\WorkshopJobService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkshopJobController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $this->currentBranchId();

        $request->validate([
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
        ]);

        $jobs = WorkshopJob::with(['jobType', 'mechanic', 'serviceOrder'])
            ->whereHas('serviceOrder', fn ($query) => $query->forBranch($branchId))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $jobTypes = JobType::where('is_active', true)->orderBy('name')->get();
        $mechanics = Mechanic::forBranch($branchId)->where('is_active', true)->orderBy('name')->get();
        $products = Product::forBranch($branchId)->where('stock', '>', 0)->orderBy('name')->get();

        return view('jobs.index', compact('jobs', 'jobTypes', 'mechanics', 'products'));
    }

    /**
     * Store a standalone job (creates a service order automatically).
     */
    public function storeStandalone(Request $request, WorkshopJobService $jobService)
    {
        $branchId = $this->requireCurrentBranchId();

        $validated = $request->validate($this->jobRules($branchId) + [
            'vehicle_info' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
        ]);

        try {
            $jobService->createStandalone($branchId, $validated, $validated['products'] ?? []);
        } catch (InsufficientStockException|BusinessRuleException $exception) {
            return back()->withErrors([
                $exception instanceof BusinessRuleException ? $exception->field : 'products' => $exception->getMessage(),
            ])->withInput();
        }

        return redirect()->route('jobs.index')->with('success', 'Trabajo creado correctamente.');
    }

    public function store(Request $request, ServiceOrder $serviceOrder, WorkshopJobService $jobService)
    {
        $this->ensureCurrentBranch($serviceOrder->branch_id);

        if (!$serviceOrder->canReceiveJobs()) {
            return back()->withErrors([
                'service_order' => 'No se pueden agregar trabajos a una orden completada o cancelada.',
            ])->withInput();
        }

        $validated = $request->validate($this->jobRules($serviceOrder->branch_id));

        try {
            $jobService->addToOrder($serviceOrder, $validated, $validated['products'] ?? []);
        } catch (InsufficientStockException|BusinessRuleException $exception) {
            return back()->withErrors([
                $exception instanceof BusinessRuleException ? $exception->field : 'products' => $exception->getMessage(),
            ])->withInput();
        }

        return redirect()->route('service-orders.show', $serviceOrder)->with('success', 'Trabajo añadido con éxito.');
    }

    /**
     * Remove a workshop job and return consumed products to their batches.
     */
    public function destroy(WorkshopJob $job, WorkshopJobService $jobService)
    {
        $serviceOrder = $job->serviceOrder;
        $this->ensureCurrentBranch($serviceOrder?->branch_id);

        try {
            $jobService->delete($job);
        } catch (BusinessRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('service-orders.show', $serviceOrder)->with('success', 'Trabajo eliminado y stock restaurado.');
    }

    public function complete(WorkshopJob $job)
    {
        $this->ensureCurrentBranch($job->serviceOrder?->branch_id);

        if (!$job->canTransitionTo(WorkshopJob::STATUS_COMPLETED)) {
            return back()->with('error', 'Este trabajo no puede marcarse como completado desde su estado actual.');
        }

        $job->update([
            'status' => WorkshopJob::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Trabajo marcado como completado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function jobRules(int $branchId): array
    {
        return [
            'job_type_id' => ['required', Rule::exists('job_types', 'id')->where('is_active', true)],
            'mechanic_id' => ['required', Rule::exists('mechanics', 'id')->where('branch_id', $branchId)->where('is_active', true)],
            'labor_cost' => 'required|numeric|min:0|max:99999999',
            'description' => 'required|string|max:255',
            'products' => 'nullable|array',
            'products.*.id' => [
                'required',
                'distinct',
                Rule::exists('products', 'id')->where('branch_id', $branchId)->whereNull('deleted_at'),
            ],
            'products.*.quantity' => 'required|integer|min:1|max:100000',
        ];
    }
}
