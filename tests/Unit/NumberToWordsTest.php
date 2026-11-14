<?php

use App\Support\NumberToWords;

it('writes Colombian pesos in words', function (int $amount, string $expected) {
    expect(NumberToWords::pesos($amount))->toBe($expected);
})->with([
    [0, 'Cero pesos m/cte.'],
    [1, 'Un peso m/cte.'],
    [21, 'Veintiún pesos m/cte.'],
    [31, 'Treinta y un pesos m/cte.'],
    [100, 'Cien pesos m/cte.'],
    [101, 'Ciento un pesos m/cte.'],
    [115, 'Ciento quince pesos m/cte.'],
    [1000, 'Mil pesos m/cte.'],
    [1001, 'Mil un pesos m/cte.'],
    [21000, 'Veintiún mil pesos m/cte.'],
    [101000, 'Ciento un mil pesos m/cte.'],
    [125000, 'Ciento veinticinco mil pesos m/cte.'],
    [404000, 'Cuatrocientos cuatro mil pesos m/cte.'],
    [1000000, 'Un millón de pesos m/cte.'],
    [1250000, 'Un millón doscientos cincuenta mil pesos m/cte.'],
    [2000000, 'Dos millones de pesos m/cte.'],
    [21500300, 'Veintiún millones quinientos mil trescientos pesos m/cte.'],
    [999999, 'Novecientos noventa y nueve mil novecientos noventa y nueve pesos m/cte.'],
]);

it('rounds decimal amounts from the database', function () {
    expect(NumberToWords::pesos('49999.60'))->toBe('Cincuenta mil pesos m/cte.');
});
