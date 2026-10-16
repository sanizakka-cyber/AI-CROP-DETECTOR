<?php

namespace App\Services\Speech\Providers;

class TtsException extends \RuntimeException
{
    /** A permanent problem (unknown voice, unsupported language): retrying will not help. */
    public bool $permanent = false;

    public static function permanent(string $message): self
    {
        $e = new self($message);
        $e->permanent = true;
        return $e;
    }
}
