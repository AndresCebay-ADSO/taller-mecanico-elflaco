<?php

namespace App\Support;

/**
 * Único mapa tono → clases (DESIGN.md §3). Las vistas nunca escriben colores de estado:
 * usan <x-status-badge> o <x-alert>, que leen de aquí.
 */
final class Tone
{
    public const NEUTRAL = 'neutral';
    public const INFO = 'info';
    public const WARNING = 'warning';
    public const SUCCESS = 'success';
    public const DANGER = 'danger';

    /** Píldora: tinte al 15 % + texto del tono. */
    public static function pill(string $tone): string
    {
        return match ($tone) {
            self::INFO => 'bg-info/15 text-info-text',
            self::WARNING => 'bg-warning/15 text-warning-text',
            self::SUCCESS => 'bg-success/15 text-success-text',
            self::DANGER => 'bg-destructive/15 text-destructive-text',
            default => 'bg-muted text-muted-foreground',
        };
    }

    /**
     * Icono de cada tono: segunda señal además del color (rojo, verde y ámbar se confunden con
     * daltonismo). Misma familia (círculo) con símbolo distinto. Neutral no lleva icono.
     */
    public static function icon(string $tone): ?string
    {
        return match ($tone) {
            self::SUCCESS => 'circle-check',
            self::WARNING => 'circle-alert',
            self::INFO => 'circle-dot',
            self::DANGER => 'circle-x',
            default => null,
        };
    }

    /** Caja de aviso: tinte suave, borde del tono y texto del tono. */
    public static function alert(string $tone): string
    {
        return match ($tone) {
            self::INFO => 'bg-info/10 border-info/30 text-info-text',
            self::WARNING => 'bg-warning/10 border-warning/30 text-warning-text',
            self::SUCCESS => 'bg-success/10 border-success/30 text-success-text',
            self::DANGER => 'bg-destructive/10 border-destructive/30 text-destructive-text',
            default => 'bg-muted border-border text-foreground',
        };
    }

    /** Solo el color de texto del tono (cifras con signo, totales anulados). */
    public static function text(string $tone): string
    {
        return match ($tone) {
            self::INFO => 'text-info-text',
            self::WARNING => 'text-warning-text',
            self::SUCCESS => 'text-success-text',
            self::DANGER => 'text-destructive-text',
            default => 'text-muted-foreground',
        };
    }

    /** Tonos aceptados por los nombres heredados de <x-badge variant="…">. */
    public static function fromLegacy(string $variant): string
    {
        return match ($variant) {
            'success', 'emerald' => self::SUCCESS,
            'error', 'red' => self::DANGER,
            'warning', 'amber' => self::WARNING,
            'brand', 'blue', 'blue-light', 'info' => self::INFO,
            default => self::NEUTRAL,
        };
    }
}
