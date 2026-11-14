<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'workshop_tax_regime' => 'nullable|string|max:100',
            'workshop_phone' => 'nullable|string|max:30',
            'workshop_email' => 'nullable|email|max:255',
            'workshop_address' => 'nullable|string|max:255',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'footer_text_invoice' => 'nullable|string|max:1000',
            'document_conditions' => 'nullable|string|max:1500',
            'intake_conditions' => 'nullable|string|max:1500',
            'document_legal_note' => 'nullable|string|max:255',
            'workshop_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:1024',
            'remove_logo' => 'nullable|boolean',
        ], [
            'workshop_logo.image' => 'El logo debe ser una imagen.',
            'workshop_logo.mimes' => 'El logo debe ser PNG, JPG o WEBP.',
            'workshop_logo.max' => 'El logo no puede pesar más de 1 MB.',
        ]);

        $this->updateLogo($request);
        unset($validated['workshop_logo'], $validated['remove_logo']);

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->back()->with('success', 'Configuración guardada.');
    }

    /**
     * El logo se guarda en storage/app/private y se sirve por la ruta branding.logo:
     * así no hace falta `storage:link` (que en Windows exige permisos de administrador).
     */
    private function updateLogo(Request $request): void
    {
        $current = Setting::where('key', 'workshop_logo')->value('value');

        if ($request->hasFile('workshop_logo')) {
            $path = $request->file('workshop_logo')->store('branding', 'local');
        } elseif ($request->boolean('remove_logo')) {
            $path = null;
        } else {
            return;
        }

        if ($current && $current !== $path) {
            Storage::disk('local')->delete($current);
        }

        Setting::updateOrCreate(['key' => 'workshop_logo'], ['value' => $path]);
    }
}
