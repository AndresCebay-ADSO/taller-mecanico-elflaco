<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('name')->paginate(10);

        return view('branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $validated['is_active'] = true;

        Branch::create($validated);

        return redirect()->route('branches.index')->with('success', 'Sucursal creada exitosamente.');
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $branch->update($validated);

        return redirect()->route('branches.index')->with('success', 'Sucursal actualizada exitosamente.');
    }

    public function destroy(Branch $branch)
    {
        $branch->update(['is_active' => false]);

        return redirect()->route('branches.index')->with('success', 'Sucursal desactivada exitosamente.');
    }

    public function toggleActive(Branch $branch)
    {
        $branch->update(['is_active' => !$branch->is_active]);

        return redirect()->route('branches.index')->with('success', 'Estado de sucursal actualizado.');
    }

    public function switch(Request $request, BranchService $branchService)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branchService->setCurrentBranch($validated['branch_id']);

        return redirect()->back()->with('success', 'Sucursal cambiada exitosamente.');
    }
}
