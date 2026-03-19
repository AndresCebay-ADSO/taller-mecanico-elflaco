<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'exists:suppliers,id,active,1',
            'upc' => 'nullable|string|max:50',
            'sale_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            
            // Conditional validations
            'purchase_price' => ($this->stock > 0) ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'initial_supplier_id' => ($this->stock > 0) ? [
                'required',
                'exists:suppliers,id',
                'in:' . implode(',', $this->input('supplier_ids', []))
            ] : 'nullable',
        ];
    }

    /**
     * Custom messages for validation.
     */
    public function messages(): array
    {
        return [
            'purchase_price.required' => 'El precio de compra es obligatorio si se ingresa stock inicial.',
            'initial_supplier_id.required' => 'Debes seleccionar el proveedor para el stock inicial.',
            'initial_supplier_id.exists' => 'El proveedor seleccionado no es válido.',
            'supplier_ids.required' => 'Debes seleccionar al menos un proveedor.',
        ];
    }
}
