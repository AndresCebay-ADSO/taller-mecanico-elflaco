<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Mechanic;

class MechanicController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $query = Mechanic::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'phone'     => 'required|digits:10',
            'email'     => 'nullable|email|unique:mechanics,email',
            'hire_date' => 'required|date',
            'is_active' => 'boolean',
        ], [
            'name.required'   => 'El nombre es obligatorio.',
            'phone.required'  => 'El teléfono es obligatorio.',
            'phone.digits'    => 'El teléfono debe tener exactamente 10 dígitos numéricos.',
            'email.email'     => 'El correo electrónico no es válido.',
            'email.unique'    => 'El correo electrónico ya está en uso.',
            'hire_date.required' => 'La fecha de contratación es obligatoria.',
            'hire_date.date'  => 'La fecha de contratación no es válida.',
        ]);

        Mechanic::create($validated);

        return redirect()->route('mechanics.index')->with('success', 'Mecánico registrado exitosamente.');
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
            'name'      => 'required|string|max:255',
            'phone'     => 'required|digits:10',
            'email'     => 'nullable|email|unique:mechanics,email,' . $mechanic->id,
            'hire_date' => 'required|date',
            'is_active' => 'boolean',
        ], [
            'name.required'   => 'El nombre es obligatorio.',
            'phone.required'  => 'El teléfono es obligatorio.',
            'phone.digits'    => 'El teléfono debe tener exactamente 10 dígitos numéricos.',
            'email.email'     => 'El correo electrónico no es válido.',
            'email.unique'    => 'El correo electrónico ya está en uso.',
            'hire_date.required' => 'La fecha de contratación es obligatoria.',
            'hire_date.date'  => 'La fecha de contratación no es válida.',
        ]);

        $mechanic->update($validated);

        return redirect()->route('mechanics.index')->with('success', 'Información del mecánico actualizada.');
    }

    public function destroy(Mechanic $mechanic)
    {
        $mechanic->delete();
        return redirect()->route('mechanics.index')->with('success', 'Mecánico eliminado exitosamente.');
    }
}