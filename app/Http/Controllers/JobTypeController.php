<?php

namespace App\Http\Controllers;

use App\Models\JobType;
use Illuminate\Http\Request;

class JobTypeController extends Controller
{
    public function index()
    {
        $jobTypes = JobType::latest()->paginate(10);
        return view('job-types.index', compact('jobTypes'));
    }

    public function create()
    {
        return view('job-types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'default_description' => 'nullable|string|max:2000',
            'calculation_type' => 'required|in:percentage,fixed',
            // Percentage fields
            'mechanic_percentage' => [
                'nullable', 'required_if:calculation_type,percentage', 'numeric', 'min:0', 'max:100',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('calculation_type') !== 'percentage' || !$request->filled('workshop_percentage')) {
                        return;
                    }
                    if (abs(((float) $value + (float) $request->input('workshop_percentage')) - 100) > 0.01) {
                        $fail('El porcentaje del mecánico y el del taller deben sumar exactamente 100%.');
                    }
                },
            ],
            'workshop_percentage' => 'nullable|required_if:calculation_type,percentage|numeric|min:0|max:100',
            // Fixed fields
            'fixed_mechanic_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0|max:99999999',
            'fixed_workshop_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0|max:99999999',
            // Toggles
            'allow_products' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Default values for percentages if not provided (summing 100)
        if ($validated['calculation_type'] === 'percentage' && !isset($validated['mechanic_percentage'])) {
            $validated['mechanic_percentage'] = 70;
            $validated['workshop_percentage'] = 30;
        }

        $validated['allow_products'] = $request->has('allow_products');
        // Casilla desmarcada = el formulario no la envía = inactivo (igual que al actualizar).
        $validated['is_active'] = $request->boolean('is_active');

        JobType::create($validated);

        return redirect()->route('job-types.index')->with('success', 'Tipo de trabajo creado.');
    }

    public function edit(JobType $jobType)
    {
        return view('job-types.edit', compact('jobType'));
    }

    public function update(Request $request, JobType $jobType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'default_description' => 'nullable|string|max:2000',
            'calculation_type' => 'required|in:percentage,fixed',
            'mechanic_percentage' => [
                'nullable', 'required_if:calculation_type,percentage', 'numeric', 'min:0', 'max:100',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('calculation_type') !== 'percentage' || !$request->filled('workshop_percentage')) {
                        return;
                    }
                    if (abs(((float) $value + (float) $request->input('workshop_percentage')) - 100) > 0.01) {
                        $fail('El porcentaje del mecánico y el del taller deben sumar exactamente 100%.');
                    }
                },
            ],
            'workshop_percentage' => 'nullable|required_if:calculation_type,percentage|numeric|min:0|max:100',
            'fixed_mechanic_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0|max:99999999',
            'fixed_workshop_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0|max:99999999',
            'allow_products' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['allow_products'] = $request->has('allow_products');
        $validated['is_active'] = $request->boolean('is_active');

        $jobType->update($validated);

        return redirect()->route('job-types.index')->with('success', 'Tipo de trabajo actualizado.');
    }

    public function destroy(JobType $jobType)
    {
        if ($jobType->is_system) {
            return back()->with('error', 'Los tipos de sistema no pueden eliminarse.');
        }

        // Con trabajos registrados no se puede borrar (historial y comisiones): se desactiva.
        if ($jobType->workshopJobs()->exists()) {
            $jobType->update(['is_active' => false]);

            return redirect()->route('job-types.index')
                ->with('info', "\"{$jobType->name}\" tiene trabajos registrados y fue desactivado: ya no aparecerá al crear trabajos.");
        }

        $jobType->delete();
        return redirect()->route('job-types.index')->with('success', 'Tipo de trabajo eliminado.');
    }
}
