<?php

if (! function_exists('money')) {
    /**
     * Formato de dinero colombiano, sin decimales: money(1250000) => "$1.250.000".
     * Es el único formato de dinero de las vistas (DESIGN.md §6).
     */
    function money(int|float|string|null $value): string
    {
        $amount = (float) ($value ?? 0);
        $formatted = number_format(abs(round($amount)), 0, ',', '.');

        return ($amount < 0 && $formatted !== '0' ? '−$' : '$') . $formatted;
    }
}
