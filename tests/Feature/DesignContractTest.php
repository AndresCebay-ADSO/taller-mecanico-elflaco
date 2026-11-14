<?php

/*
 * Contrato de interfaz (DESIGN.md). Revisa todas las vistas Blade y falla si aparece algo prohibido.
 * Una excepción se agrega en $exceptions con su motivo, nunca borrando la regla.
 */

use Symfony\Component\Finder\Finder;

$rules = [
    'paleta cruda de Tailwind (usar tokens: bg-card, text-muted-foreground…)' =>
        '/(?<![\w-])(?:[a-z-]+:)*(?:bg|text|border|ring|divide|from|to|via|placeholder|fill|stroke|outline|accent|shadow)-(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|brand|navy)-\d{2,3}\b/',
    'blanco/negro puros (usar bg-card, text-foreground…)' =>
        '/(?<![\w-])(?:[a-z-]+:)*(?:bg|text|border)-(?:white|black)\b/',
    'pesos 700+ (solo font-normal, font-medium, font-semibold)' =>
        '/(?<![\w-])font-(?:bold|extrabold|black)\b/',
    'tamaños de letra arbitrarios (usar la escala: text-xs, text-sm…)' =>
        '/(?<![\w-])text-\[\d/',
    'radios grandes o arbitrarios (rounded-lg en tarjetas, rounded-md en controles)' =>
        '/(?<![\w-])rounded(?:-[trbl]{1,2})?-(?:2xl|3xl|\[)/',
    'confirm()/alert() del navegador (usar data-confirm y <x-confirm-dialog>)' =>
        '/(?<![\w:>$.])(?:window\.)?(?:confirm|alert)\(/',
    'type="number" (usar inputmode o <x-input is-money>)' =>
        '/type="number"/',
    'fuentes externas (la fuente va incluida con @fontsource)' =>
        '/fonts\.(?:googleapis|gstatic|bunny)/',
    'number_format en vistas (usar money())' =>
        '/number_format\(/',
    'colores hex en vistas (usar tokens)' =>
        '/#[0-9a-fA-F]{6}\b|#[0-9a-fA-F]{3}\b(?![0-9a-fA-F])/',
];

// Archivo (relativo a resources/views) => [regla => motivo].
$exceptions = [
    'pagination/app.blade.php' => [
        'number_format en vistas (usar money())' => 'Cuenta registros, no es dinero.',
    ],
];

// Ruta directa: este archivo se lee antes de que arranque la aplicación.
$views = collect((new Finder)->files()->in(dirname(__DIR__, 2) . '/resources/views')->name('*.blade.php'))
    ->mapWithKeys(fn ($file) => [str_replace('\\', '/', $file->getRelativePathname()) => $file->getContents()]);

it('finds the views to check', function () use ($views) {
    expect($views->count())->toBeGreaterThan(40);
});

foreach ($rules as $ruleName => $pattern) {
    it("has no {$ruleName}", function () use ($views, $pattern, $ruleName, $exceptions) {
        $violations = [];

        foreach ($views as $path => $contents) {
            if (isset($exceptions[$path][$ruleName])) {
                continue;
            }

            foreach (preg_split('/\R/', $contents) as $index => $line) {
                if (preg_match($pattern, $line, $match)) {
                    $violations[] = "{$path}:" . ($index + 1) . " → {$match[0]}";
                }
            }
        }

        expect($violations)->toBe([], "Reglas de DESIGN.md incumplidas:\n" . implode("\n", $violations));
    });
}
