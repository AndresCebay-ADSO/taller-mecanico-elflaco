<?php

namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->all();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $keys = [
            'workshop_name', 'workshop_nit', 'workshop_phone', 
            'workshop_email', 'workshop_address', 'tax_percentage', 
            'footer_text_invoice'
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $request->get($key)]);
            }
        }

        return redirect()->back()->with('status', 'Configuración actualizada exitosamente.');
    }
}
