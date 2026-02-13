<?php

namespace App\Exceptions;

use Exception;

class InvalidDateRangeException extends Exception
{
    public function __construct(string $message = "Invalid date range provided")
    {
        parent::__construct($message, 422);
    }
}
