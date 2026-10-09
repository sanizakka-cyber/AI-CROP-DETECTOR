<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Rejects a genuinely zero-byte file without constraining how small a
 * legitimately non-empty file may be.
 *
 * Laravel's built-in 'min:N' file-size rule only has whole-kilobyte
 * granularity (it compares $file->getSize() / 1024 against N) -- 'min:1'
 * rejects not just a 0-byte file but anything under 1024 bytes, which
 * caught several of this app's own small-but-real test fixtures (a
 * magic-bytes-only fake JPEG, a default 10x10px UploadedFile::fake()->
 * image()) as a false positive. This rule checks the exact byte count
 * instead, so only a truly empty upload is rejected.
 */
class NotEmptyFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof UploadedFile && $value->getSize() === 0) {
            $fail('The :attribute file is empty.');
        }
    }
}
