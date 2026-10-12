<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Phase 9 §A2/A5: a route parameter constrained to digits only
     * (AppServiceProvider::constrainIdRouteParameters()) can still be
     * too large to represent as a PHP/Postgres bigint -- e.g. a 30-digit
     * number. A controller method that type-hints that parameter as
     * `int` doesn't get a clean TypeError for this specific case the
     * way it does for a non-numeric string; PHP's scalar coercion for
     * an out-of-range numeric string produced an unhandled 500 instead
     * (found via NotificationApiController's regression test). Call
     * this first, with the parameter typed `string` instead of `int`,
     * rather than relying on PHP's coercion behavior for an edge case
     * it doesn't handle predictably.
     */
    protected function abortIfNotRepresentableId(string $id): void
    {
        if (! ctype_digit($id) || $id > (string) PHP_INT_MAX) {
            abort(404, 'Resource not found.');
        }
    }
}
