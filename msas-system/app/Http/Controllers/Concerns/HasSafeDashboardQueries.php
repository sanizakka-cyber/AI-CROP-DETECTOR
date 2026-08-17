<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Log;

// Shared by any controller whose dashboard queries have historically caught
// failures and shown 0/empty instead of surfacing them. safe() preserves
// that same fallback behavior — a broken query still can't crash the page —
// but also records which stat failed so the view can show a real error
// banner instead of a silent 0.
trait HasSafeDashboardQueries
{
    private array $dashboardErrors = [];

    private function safe(string $label, \Closure $query, mixed $fallback = 0): mixed
    {
        try {
            return $query();
        } catch (\Throwable $e) {
            Log::error("[Dashboard:{$label}] " . $e->getMessage());
            $this->dashboardErrors[] = $label;
            return $fallback;
        }
    }
}
