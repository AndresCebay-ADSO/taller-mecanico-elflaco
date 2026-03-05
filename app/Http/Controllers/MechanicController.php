<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Mechanic;

class MechanicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mechanics = Mechanic::latest()->paginate(10);
        return view('mechanics.index', compact('mechanics'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('mechanics.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:mechanics,email',
            'hire_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        Mechanic::create($validated);

        return redirect()->route('mechanics.index')->with('success', 'Mecánico registrado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Mechanic $mechanic)
    {
        return view('mechanics.show', compact('mechanic'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mechanic $mechanic)
    {
        return view('mechanics.edit', compact('mechanic'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mechanic $mechanic)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|unique:mechanics,email,' . $mechanic->id,
            'hire_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $mechanic->update($validated);

        return redirect()->route('mechanics.index')->with('success', 'Información del mecánico actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mechanic $mechanic)
    {
        $mechanic->delete();
        return redirect()->route('mechanics.index')->with('success', 'Mecánico eliminado exitosamente.');
    }
}
