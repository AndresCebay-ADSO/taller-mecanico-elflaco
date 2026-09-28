<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violacion de una regla de negocio que debe mostrarse al usuario como mensaje,
 * no como error 500. `field` indica el campo del formulario al que se asocia.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'general')
    {
        parent::__construct($message);
    }
}
