<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $query = Supplier::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->latest()->paginate(10)->appends($request->all());
        return view('suppliers.index', compact('suppliers'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:30',
            'phone'   => 'required|digits:10',
            'email'   => 'nullable|email|max:255',
            'address' => 'nullable|string|max:50',
        ], [
            'name.required'  => 'El nombre es obligatorio.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.digits' => 'El teléfono debe tener exactamente 10 dígitos numéricos.',
            'email.email'    => 'El correo electrónico no es válido.',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        return view('suppliers.show', compact('supplier'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        abort(404);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:30',
            'phone'   => 'required|digits:10',
            'email'   => 'nullable|email|max:255',
            'address' => 'nullable|string|max:50',
        ], [
            'name.required'  => 'El nombre es obligatorio.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.digits'   => 'El teléfono debe tener exactamente 10 dígitos.',
            'email.email'    => 'El correo electrónico no es válido.',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Proveedor actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $hasProducts = $supplier->products()->exists();
        $hasPurchases = $supplier->productPurchases()->exists();

        if ($hasProducts || $hasPurchases) {
            $supplier->update(['active' => false]);
            return redirect()->route('suppliers.index')
                ->with('success', 'El proveedor tiene registros asociados y fue desactivado.');
        }

        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Proveedor eliminado exitosamente.');
    }

    public function toggleActive(Supplier $supplier)
    {
        $supplier->update(['active' => !$supplier->active]);
        $status = $supplier->active ? 'activado' : 'desactivado';
        
        return redirect()->route('suppliers.index')
            ->with('success', "Proveedor {$status} exitosamente.");
    }
}