<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Datos de ejemplo que deja el SettingSeeder. Salen impresos en los comprobantes: si siguen así, hay que avisar.
     * Clave => nombre para el aviso.
     */
    public const EXAMPLE_VALUES = [
        'workshop_nit' => ['123456789-0', 'NIT'],
        'workshop_phone' => ['+57 321 000 0000', 'teléfono'],
        'workshop_email' => ['contacto@elflaco.com', 'correo'],
        'workshop_address' => ['Calle 123 # 45 - 67, Medellín', 'dirección'],
    ];

    /**
     * Los datos del taller que siguen con el valor de ejemplo, por su nombre («NIT», «dirección»…).
     *
     * @return list<string>
     */
    public static function examplesInUse(): array
    {
        $values = self::whereIn('key', array_keys(self::EXAMPLE_VALUES))->pluck('value', 'key');

        return array_values(array_map(
            fn (string $key) => self::EXAMPLE_VALUES[$key][1],
            array_filter(array_keys(self::EXAMPLE_VALUES), fn (string $key) => ($values[$key] ?? null) === self::EXAMPLE_VALUES[$key][0]),
        ));
    }
}
