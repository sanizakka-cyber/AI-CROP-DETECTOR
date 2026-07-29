<?php

namespace App\Exceptions;

class OtpInvalidException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $attemptsLeft = 0)
    {
        parent::__construct($message);
    }
}
