<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Server-side HEIC/HEIF -> JPEG for scan uploads, so a scan never depends on
 * the browser or phone converting (iPhone Safari may upload HEIC as-is).
 *
 * Driver: Imagick when the extension is installed with HEIC support, else the
 * AI engine's /convert/image (Pillow + pillow-heif, which bundles libheif).
 * Both apply orientation and give sRGB JPEG.
 */
class HeicConverter
{
    /** ISO-BMFF brands used by HEIC/HEIF stills and sequences. */
    private const BRANDS = ['heic', 'heix', 'heim', 'heis', 'hevc', 'hevx', 'mif1', 'msf1'];

    public function isHeic(UploadedFile $file): bool
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($ext, ['heic', 'heif'], true)) {
            return true;
        }
        $mime = strtolower((string) $file->getClientMimeType());
        if (str_starts_with($mime, 'image/heic') || str_starts_with($mime, 'image/heif')) {
            return true;
        }
        // Content check: a HEIC renamed to .jpg is still HEIC.
        $head = @file_get_contents($file->getRealPath(), false, null, 0, 12);
        return is_string($head) && strlen($head) === 12 && substr($head, 4, 4) === 'ftyp'
            && in_array(substr($head, 8, 4), self::BRANDS, true);
    }

    /**
     * @throws \RuntimeException when the photo cannot be converted
     */
    public function toJpeg(UploadedFile $file): UploadedFile
    {
        $bytes = $this->imagickAvailable() ? $this->viaImagick($file) : $this->viaEngine($file);

        $path = tempnam(sys_get_temp_dir(), 'heic') . '.jpg';
        file_put_contents($path, $bytes);
        $name = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'photo';

        // test=true: the converted file was not itself "uploaded", but it stands in for one.
        return new UploadedFile($path, "{$name}.jpg", 'image/jpeg', null, true);
    }

    public function driver(): string
    {
        return $this->imagickAvailable() ? 'imagick' : 'engine';
    }

    private function imagickAvailable(): bool
    {
        $pref = config('services.heic.driver', 'auto');
        if ($pref === 'engine' || !extension_loaded('imagick')) {
            return false;
        }
        try {
            return !empty(\Imagick::queryFormats('HEIC'));
        } catch (\Throwable) {
            return false;
        }
    }

    private function viaImagick(UploadedFile $file): string
    {
        try {
            $im = new \Imagick($file->getRealPath());
            $im->setIteratorIndex(0);
            $im->autoOrient();
            $im->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
            $im->setImageFormat('jpeg');
            $im->setImageCompressionQuality(92);
            $im->stripImage();
            return $im->getImageBlob();
        } catch (\Throwable $e) {
            Log::warning('HEIC conversion failed (imagick)', ['error' => $e->getMessage()]);
            throw new \RuntimeException('This photo could not be converted. Please choose a JPEG or PNG photo.', previous: $e);
        }
    }

    private function viaEngine(UploadedFile $file): string
    {
        $base = rtrim((string) config('services.ai_engine.url'), '/');
        try {
            $http = Http::timeout(60)->connectTimeout(30);
            if ($key = config('services.ai_engine.key')) {
                $http = $http->withToken($key);
            }
            $res = $http->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName() ?: 'photo.heic')
                ->post("{$base}/convert/image");
        } catch (\Throwable $e) {
            Log::warning('HEIC conversion failed (engine unreachable)', ['error' => $e->getMessage()]);
            throw new \RuntimeException('This photo could not be converted right now. Please try again, or choose a JPEG or PNG photo.', previous: $e);
        }
        if (!$res->successful() || !str_starts_with((string) $res->header('Content-Type'), 'image/jpeg') || $res->body() === '') {
            Log::warning('HEIC conversion failed (engine)', ['status' => $res->status()]);
            throw new \RuntimeException('This photo could not be converted. Please choose a JPEG or PNG photo.');
        }
        return $res->body();
    }
}
