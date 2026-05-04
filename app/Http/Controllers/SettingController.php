<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->all();

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'workshop_name' => 'nullable|string|max:255',
            'workshop_nit' => 'nullable|string|max:50',
            'workshop_phone' => 'nullable|string|max:30',
            'workshop_email' => 'nullable|email|max:255',
            'workshop_address' => 'nullable|string|max:255',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'footer_text_invoice' => 'nullable|string|max:1000',
        ]);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->back()->with('status', 'Configuracion actualizada exitosamente.');
    }
}
