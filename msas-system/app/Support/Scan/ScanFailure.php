<?php

namespace App\Support\Scan;

/**
 * One classified scan failure: what went wrong (code), where (service),
 * what the upstream said (status + raw detail, masked before it is stored
 * or shown), and anything the farmer's message needs (field, limit, date).
 */
final class ScanFailure
{
    public function __construct(
        public readonly string $code,
        public readonly ?int $upstreamStatus = null,
        public readonly ?string $detail = null,
        public readonly ?string $service = null,
        public readonly array $extra = [],
    ) {
    }

    public function service(): string
    {
        return $this->service ?? ScanErrorCode::meta($this->code)['service'];
    }
}
