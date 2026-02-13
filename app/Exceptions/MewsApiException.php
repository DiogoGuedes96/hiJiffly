<?php

namespace App\Exceptions;

use Exception;

class MewsApiException extends Exception
{
    public function __construct(string $message = "Mews API request failed", int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
