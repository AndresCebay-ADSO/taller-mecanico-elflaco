<?php

namespace App\Http\Controllers;

use App\Models\JobType;
use Illuminate\Http\Request;

class JobTypeController extends Controller
{
    public function index()
    {
        $jobTypes = JobType::all();
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
            'base_price' => 'required|numeric|min:0',
        ]);

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
            'base_price' => 'required|numeric|min:0',
        ]);

        $jobType->update($validated);

        return redirect()->route('job-types.index')->with('success', 'Tipo de trabajo actualizado.');
    }

    public function destroy(JobType $jobType)
    {
        $jobType->delete();
        return redirect()->route('job-types.index')->with('success', 'Tipo de trabajo eliminado.');
    }
}
