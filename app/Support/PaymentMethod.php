<?php

namespace App\Support;

/**
 * Formas de pago que acepta el taller, iguales para ventas de mostrador y comprobantes de servicio.
 */
final class PaymentMethod
{
    public const CASH = 'Efectivo';

    public const ALL = ['Efectivo', 'Nequi', 'Daviplata', 'Transferencia', 'Tarjeta', 'Otro'];

    /** Texto para pagos guardados antes de que se registrara el método. */
    public const UNKNOWN = 'Sin registrar';

    public static function rule(): string
    {
        return 'in:' . implode(',', self::ALL);
    }

    public static function label(?string $method): string
    {
        return $method ?: self::UNKNOWN;
    }
}
