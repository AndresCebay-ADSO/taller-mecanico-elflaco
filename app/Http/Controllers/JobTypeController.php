<?php

namespace App\Http\Controllers;

use App\Models\JobType;
use Illuminate\Http\Request;

class JobTypeController extends Controller
{
    public function index()
    {
        $jobTypes = JobType::paginate(10);
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
            'description' => 'required|string',
            'default_description' => 'nullable|string',
            'calculation_type' => 'required|in:percentage,fixed',
            // Percentage fields
            'mechanic_percentage' => 'nullable|required_if:calculation_type,percentage|numeric|min:0|max:100',
            'workshop_percentage' => 'nullable|required_if:calculation_type,percentage|numeric|min:0|max:100',
            'percentage_fixed_total' => 'nullable|numeric|min:0',
            // Fixed fields
            'fixed_mechanic_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0',
            'fixed_workshop_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0',
            // Toggles
            'allow_products' => 'boolean',
            'allow_custom_labor' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Default values for percentages if not provided (summing 100)
        if ($validated['calculation_type'] === 'percentage' && !isset($validated['mechanic_percentage'])) {
            $validated['mechanic_percentage'] = 70;
            $validated['workshop_percentage'] = 30;
        }

        $validated['allow_products'] = $request->has('allow_products');
        $validated['allow_custom_labor'] = $request->has('allow_custom_labor');
        $validated['is_active'] = $request->boolean('is_active', true); // Default to active true if not present

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
            'description' => 'required|string',
            'default_description' => 'nullable|string',
            'calculation_type' => 'required|in:percentage,fixed',
            'mechanic_percentage' => 'nullable|required_if:calculation_type,percentage|numeric|min:0|max:100',
            'workshop_percentage' => 'nullable|required_if:calculation_type,percentage|numeric|min:0|max:100',
            'percentage_fixed_total' => 'nullable|numeric|min:0',
            'fixed_mechanic_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0',
            'fixed_workshop_amount' => 'nullable|required_if:calculation_type,fixed|numeric|min:0',
            'allow_products' => 'boolean',
            'allow_custom_labor' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['allow_products'] = $request->has('allow_products');
        $validated['allow_custom_labor'] = $request->has('allow_custom_labor');
        $validated['is_active'] = $request->boolean('is_active');

        $jobType->update($validated);

        return redirect()->route('job-types.index')->with('success', 'Tipo de trabajo actualizado.');
    }

    public function destroy(JobType $jobType)
    {
        if ($jobType->is_system) {
            return back()->with('error', 'Los tipos de sistema no pueden eliminarse.');
        }

        $jobType->delete();
        return redirect()->route('job-types.index')->with('success', 'Tipo de trabajo eliminado.');
    }
}
