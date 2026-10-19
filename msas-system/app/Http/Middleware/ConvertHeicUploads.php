<?php

namespace App\Http\Middleware;

use App\Services\HeicConverter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converts any HEIC/HEIF photo in a scan upload to JPEG before validation,
 * storage and the AI call (web form and app API alike). Other files pass
 * through untouched. A photo that cannot be converted gets a clear message.
 */
class ConvertHeicUploads
{
    public function __construct(private HeicConverter $converter)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Read the raw Symfony file bag (not $request->allFiles(), which caches
        // the converted Laravel objects) and replace HEIC entries in place.
        $files = $request->files->all();
        try {
            $changed = false;
            array_walk_recursive($files, function (&$file) use (&$changed) {
                if ($file instanceof UploadedFile && $file->isValid() && $this->converter->isHeic($file)) {
                    $file = $this->converter->toJpeg($file);
                    $changed = true;
                }
            });
        } catch (\RuntimeException $e) {
            $field = array_key_first($files) ?? 'image';
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => $e->getMessage(), 'details' => [$field => [$e->getMessage()]]], 422);
            }
            return back()->withErrors([$field => $e->getMessage()])->withInput();
        }

        if ($changed) {
            $request->files->replace($files);
        }
        return $next($request);
    }
}
