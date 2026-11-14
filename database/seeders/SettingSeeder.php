<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'workshop_name' => 'MotoTaller El Flaco',
            'workshop_nit' => '123456789-0',
            'workshop_phone' => '+57 321 000 0000',
            'workshop_email' => 'contacto@elflaco.com',
            'workshop_address' => 'Calle 123 # 45 - 67, Medellín',
            'tax_percentage' => '19',
            'footer_text_invoice' => 'Gracias por confiar en MotoTaller El Flaco.',
        ];

        // Solo los que faltan: volver a correr el seeder no debe borrar la configuración real del taller.
        foreach ($settings as $key => $value) {
            \App\Models\Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
