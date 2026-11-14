<?php

namespace App\Support;

/**
 * Valor en letras para el comprobante: 125000 => "Ciento veinticinco mil pesos m/cte."
 * Escrito a mano porque NumberFormatter (spellout) necesita la extensión intl, que XAMPP no trae activa.
 */
final class NumberToWords
{
    private const UNITS = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
        'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve',
        'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete',
        'veintiocho', 'veintinueve'];

    private const TENS = [3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta', 7 => 'setenta',
        8 => 'ochenta', 9 => 'noventa'];

    private const HUNDREDS = [1 => 'ciento', 2 => 'doscientos', 3 => 'trescientos', 4 => 'cuatrocientos',
        5 => 'quinientos', 6 => 'seiscientos', 7 => 'setecientos', 8 => 'ochocientos', 9 => 'novecientos'];

    /** Pesos colombianos en letras, con la primera letra en mayúscula. */
    public static function pesos(int|float|string $amount): string
    {
        $value = (int) round((float) $amount);
        $negative = $value < 0;
        $value = abs($value);

        if ($value === 0) {
            $text = 'cero pesos';
        } elseif ($value === 1) {
            $text = 'un peso';
        } else {
            $words = self::number($value, true);
            // «Un millón de pesos», «dos millones de pesos»; «un millón doscientos mil pesos».
            $text = $value % 1_000_000 === 0 ? "{$words} de pesos" : "{$words} pesos";
        }

        $text = ($negative ? 'menos ' : '') . $text . ' m/cte.';

        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }

    /** Número en letras. $beforeNoun apocopa el final: «veintiún pesos», «un mil» no existe (es «mil»). */
    public static function number(int $value, bool $beforeNoun = false): string
    {
        if ($value === 0) {
            return 'cero';
        }

        $millions = intdiv($value, 1_000_000);
        $thousands = intdiv($value % 1_000_000, 1000);
        $rest = $value % 1000;
        $parts = [];

        if ($millions > 0) {
            $parts[] = $millions === 1 ? 'un millón' : self::number($millions, true) . ' millones';
        }
        if ($thousands > 0) {
            $parts[] = $thousands === 1 ? 'mil' : self::belowThousand($thousands, true) . ' mil';
        }
        if ($rest > 0) {
            $parts[] = self::belowThousand($rest, $beforeNoun);
        }

        return implode(' ', $parts);
    }

    private static function belowThousand(int $value, bool $apocope): string
    {
        if ($value === 100) {
            return 'cien';
        }

        $hundreds = intdiv($value, 100);
        $rest = $value % 100;
        $parts = $hundreds > 0 ? [self::HUNDREDS[$hundreds]] : [];

        if ($rest > 0) {
            if ($rest < 30) {
                $word = self::UNITS[$rest];
            } else {
                $word = self::TENS[intdiv($rest, 10)] . ($rest % 10 ? ' y ' . self::UNITS[$rest % 10] : '');
            }
            if ($apocope) {
                $word = preg_replace(['/veintiuno$/', '/(^|\s)uno$/'], ['veintiún', '$1un'], $word);
            }
            $parts[] = $word;
        }

        return implode(' ', $parts);
    }
}
