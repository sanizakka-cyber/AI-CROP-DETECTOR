<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use Tests\TestCase;

/**
 * Permissions-Policy must let msasagro.com itself use location, the
 * microphone and the camera. It was "geolocation=()" etc., which made the
 * browser refuse "Use my location" (always "Location permission denied")
 * and the AI assistant's voice input without even asking the user. Other
 * sites embedding ours still get nothing, and payment stays off.
 */
class SecurityHeadersTest extends TestCase
{
    /** "camera=(self), payment=()" -> ['camera' => '(self)', 'payment' => '()'] */
    private function parse(string $header): array
    {
        $out = [];
        foreach (array_map('trim', explode(',', $header)) as $part) {
            [$k, $v] = array_map('trim', explode('=', $part, 2)) + [1 => ''];
            $out[$k] = $v;
        }
        return $out;
    }

    public function test_the_site_may_use_location_microphone_and_camera_itself(): void
    {
        $header = $this->get('/login')->assertOk()->headers->get('Permissions-Policy');
        $this->assertNotNull($header, 'Permissions-Policy header missing');
        $policy = $this->parse($header);

        foreach (['geolocation', 'microphone', 'camera'] as $feature) {
            $this->assertSame('(self)', $policy[$feature] ?? null, "{$feature} must be allowed for the site itself");
        }
        $this->assertSame('()', $policy['payment'] ?? null, 'payment stays off');
        // Never opened to every origin.
        $this->assertStringNotContainsString('*', $header);
    }

    public function test_nginx_sends_the_same_policy(): void
    {
        // nginx adds the header to every response (static files included); PHP
        // pages get it from the middleware too, so the two must never disagree.
        $conf = file_get_contents(base_path('docker/nginx.conf'));
        $this->assertMatchesRegularExpression('/add_header Permissions-Policy "([^"]+)" always;/', $conf);
        preg_match('/add_header Permissions-Policy "([^"]+)" always;/', $conf, $m);
        $this->assertSame(SecurityHeaders::PERMISSIONS_POLICY, $m[1]);
    }
}
