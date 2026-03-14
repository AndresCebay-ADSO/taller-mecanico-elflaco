<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = "Stock insuficiente para completar la operación.")
    {
        parent::__construct($message);
    }
}
