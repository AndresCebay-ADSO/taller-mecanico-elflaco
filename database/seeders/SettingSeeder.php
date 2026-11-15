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
            // De ejemplo: el inicio y taller:verificar avisan mientras no se cambien (Setting::EXAMPLE_VALUES).
            'workshop_nit' => \App\Models\Setting::EXAMPLE_VALUES['workshop_nit'][0],
            'workshop_phone' => \App\Models\Setting::EXAMPLE_VALUES['workshop_phone'][0],
            'workshop_email' => \App\Models\Setting::EXAMPLE_VALUES['workshop_email'][0],
            'workshop_address' => \App\Models\Setting::EXAMPLE_VALUES['workshop_address'][0],
            'tax_percentage' => '19',
            'footer_text_invoice' => 'Gracias por confiar en MotoTaller El Flaco.',
        ];

        // Solo los que faltan: volver a correr el seeder no debe borrar la configuración real del taller.
        foreach ($settings as $key => $value) {
            \App\Models\Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
