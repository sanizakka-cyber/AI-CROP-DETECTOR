<?php

namespace App\Exceptions;

use App\Support\Scan\ScanFailure;
use RuntimeException;

/**
 * Thrown by the scan flows (web and app) when a scan cannot complete.
 * bootstrap/app.php renders it through ScanErrorReporter: recorded with a
 * reference, explained to the farmer, detailed for staff.
 */
class ScanFailedException extends RuntimeException
{
    public function __construct(public readonly ScanFailure $failure, ?\Throwable $previous = null)
    {
        parent::__construct($failure->code, 0, $previous);
    }
}
