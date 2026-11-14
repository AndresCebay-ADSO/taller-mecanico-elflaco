<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Estado de los respaldos que deja scripts\windows\respaldo.bat (taller_*.zip en config('taller.respaldos_dir')).
 * Nadie revisa el Programador de tareas: si los respaldos se detienen, la app tiene que decirlo.
 */
final class BackupStatus
{
    public function __construct(
        private readonly ?string $directory,
        private readonly int $warnAfterDays,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(config('taller.respaldos_dir'), config('taller.respaldos_dias_aviso', 3));
    }

    public function isConfigured(): bool
    {
        return filled($this->directory);
    }

    /** Fecha del respaldo más reciente, o null si no hay ninguno (o la carpeta no existe). */
    public function latest(): ?CarbonImmutable
    {
        if (! $this->isConfigured() || ! is_dir($this->directory)) {
            return null;
        }

        $times = array_map('filemtime', glob(rtrim($this->directory, '\\/') . DIRECTORY_SEPARATOR . 'taller_*.zip') ?: []);

        return $times ? CarbonImmutable::createFromTimestamp(max($times), config('app.timezone')) : null;
    }

    /** true si hay que avisar: configurado y sin respaldos, o el último es más viejo que el límite. */
    public function needsAttention(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $latest = $this->latest();

        return $latest === null || $latest->lt(now()->subDays($this->warnAfterDays));
    }

    /** Texto para el aviso: «Hace 5 días» o «Nunca». */
    public function describe(): string
    {
        $latest = $this->latest();

        return $latest === null
            ? 'No se encontró ningún respaldo en ' . $this->directory . '.'
            : 'El último respaldo es de ' . $latest->locale('es')->diffForHumans() . ' (' . $latest->format('d/m/Y h:i a') . ').';
    }
}
