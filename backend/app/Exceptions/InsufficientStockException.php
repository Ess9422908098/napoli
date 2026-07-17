<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    /** @param array<int, array<string, mixed>> $shortages */
    public function __construct(string $message, public array $shortages = [])
    {
        parent::__construct($message);
    }
}
