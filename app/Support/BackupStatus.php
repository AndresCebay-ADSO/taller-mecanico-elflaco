<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Estado de los respaldos que deja scripts\windows\respaldo.bat (taller_*.zip) en una carpeta: la del portátil
 * (config('taller.respaldos_dir')) o la de Google Drive (config('taller.respaldos_nube')).
 * Nadie revisa el Programador de tareas ni respaldo.log: si los respaldos se detienen, la app tiene que decirlo.
 */
final class BackupStatus
{
    public const LOCAL = 'local';

    public const CLOUD = 'nube';

    public function __construct(
        private readonly ?string $directory,
        private readonly int $warnAfterDays,
        public readonly string $place = self::LOCAL,
    ) {
    }

    /** Respaldos del portátil. */
    public static function fromConfig(): self
    {
        return new self(config('taller.respaldos_dir'), (int) config('taller.respaldos_dias_aviso', 3));
    }

    /** Copia en Google Drive: si el portátil se daña o se pierde, es lo único que queda. */
    public static function cloudFromConfig(): self
    {
        return new self(config('taller.respaldos_nube'), (int) config('taller.respaldos_dias_aviso', 3), self::CLOUD);
    }

    /**
     * Los que hay que mostrar en el inicio. Si el portátil no está respaldando, la nube tampoco recibe nada:
     * basta con ese aviso.
     *
     * @return list<self>
     */
    public static function alerts(): array
    {
        $local = self::fromConfig();
        if ($local->needsAttention()) {
            return [$local];
        }

        $cloud = self::cloudFromConfig();

        return $cloud->needsAttention() ? [$cloud] : [];
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

    /** Título del aviso. */
    public function headline(): string
    {
        return $this->place === self::CLOUD
            ? 'Los respaldos no están llegando a Google Drive.'
            : 'Los datos no se están respaldando.';
    }

    /** Qué hacer. */
    public function hint(): string
    {
        return $this->place === self::CLOUD
            ? 'Abre Google Drive para escritorio y revisa que el portátil tenga internet: si el portátil se daña o se pierde, solo quedan los respaldos de la nube.'
            : 'Revisa que el portátil haya estado encendido a la hora del respaldo o ejecuta respaldo.bat.';
    }

    /** «El último respaldo es de hace 5 días» o «No se encontró ningún respaldo en …». */
    public function describe(): string
    {
        $latest = $this->latest();

        return $latest === null
            ? 'No se encontró ningún respaldo en ' . $this->directory . '.'
            : 'El último respaldo es de ' . $latest->locale('es')->diffForHumans() . ' (' . $latest->format('d/m/Y h:i a') . ').';
    }
}
