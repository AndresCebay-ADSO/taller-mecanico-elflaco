<?php

namespace App\Http\Controllers;

use App\Models\Mechanic;
use App\Services\BranchService;
use Illuminate\Http\Request;

class MechanicController extends Controller
{
    public function index(Request $request, BranchService $branchService)
    {
        $branchId = $branchService->getCurrentBranch()?->id;
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $query = Mechanic::forBranch($branchId);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $mechanics = $query->latest()->paginate(10)->appends($request->all());

        return view('mechanics.index', compact('mechanics'));
    }

    public function create()
    {
        return view('mechanics.create');
    }

    public function store(Request $request, BranchService $branchService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:30',
            'phone' => 'required|digits:10',
            'email' => 'nullable|email|unique:mechanics,email',
            'hire_date' => 'required|date',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'phone.required' => 'El telefono es obligatorio.',
            'phone.digits' => 'El telefono debe tener exactamente 10 digitos numericos.',
            'email.email' => 'El correo electronico no es valido.',
            'email.unique' => 'El correo electronico ya esta en uso.',
            'hire_date.required' => 'La fecha de contratacion es obligatoria.',
            'hire_date.date' => 'La fecha de contratacion no es valida.',
        ]);

        $validated['branch_id'] = $branchService->getCurrentBranch()?->id;
        $validated['is_active'] = $validated['is_active'] ?? true;

        Mechanic::create($validated);

        return redirect()->route('mechanics.index')->with('success', 'Mecanico registrado exitosamente.');
    }

    public function show(Mechanic $mechanic)
    {
        return view('mechanics.show', compact('mechanic'));
    }

    public function edit(Mechanic $mechanic)
    {
        return view('mechanics.edit', compact('mechanic'));
    }

    public function update(Request $request, Mechanic $mechanic)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:30',
            'phone' => 'required|digits:10',
            'email' => 'nullable|email|unique:mechanics,email,' . $mechanic->id,
            'hire_date' => 'required|date',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'phone.required' => 'El telefono es obligatorio.',
            'phone.digits' => 'El telefono debe tener exactamente 10 digitos numericos.',
            'email.email' => 'El correo electronico no es valido.',
            'email.unique' => 'El correo electronico ya esta en uso.',
            'hire_date.required' => 'La fecha de contratacion es obligatoria.',
            'hire_date.date' => 'La fecha de contratacion no es valida.',
        ]);

        $mechanic->update($validated);

        return redirect()->route('mechanics.index')->with('success', 'Informacion del mecanico actualizada.');
    }

    public function destroy(Mechanic $mechanic)
    {
        if ($mechanic->workshopJobs()->exists()) {
            $mechanic->update(['is_active' => false]);

            return redirect()->route('mechanics.index')
                ->with('info', 'El mecanico tiene historial asociado y fue desactivado.');
        }

        $mechanic->delete();

        return redirect()->route('mechanics.index')->with('success', 'Mecanico eliminado exitosamente.');
    }
}
