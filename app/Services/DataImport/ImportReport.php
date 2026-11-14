<?php

namespace App\Services\DataImport;

/**
 * Resultado de revisar (o importar) un archivo: qué se va a crear, qué se omite por existir ya,
 * los errores que impiden importar y los avisos que no lo impiden.
 */
final class ImportReport
{
    /** @var array<string, list<array<string, mixed>>> hoja => filas listas para crear */
    public array $create = [];

    /** @var array<string, list<string>> hoja => nombres que ya existen (se omiten) */
    public array $skip = [];

    /** @var list<array{sheet: string, row: int, message: string}> */
    public array $errors = [];

    /** @var list<array{sheet: string, row: int, message: string}> */
    public array $warnings = [];

    public function error(string $sheet, int $row, string $message): void
    {
        $this->errors[] = compact('sheet', 'row', 'message');
    }

    public function warning(string $sheet, int $row, string $message): void
    {
        $this->warnings[] = compact('sheet', 'row', 'message');
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function countCreate(?string $sheet = null): int
    {
        return $sheet ? count($this->create[$sheet] ?? []) : array_sum(array_map('count', $this->create));
    }

    public function countSkip(?string $sheet = null): int
    {
        return $sheet ? count($this->skip[$sheet] ?? []) : array_sum(array_map('count', $this->skip));
    }

    public function isEmpty(): bool
    {
        return $this->countCreate() === 0 && $this->countSkip() === 0 && ! $this->hasErrors();
    }
}
