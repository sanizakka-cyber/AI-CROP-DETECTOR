<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fonts come from our own site (public/fonts, public/css/fonts.css), so the
 * Content Security Policy never has to allow another font domain; and the
 * sign-in/registration side panel makes no claims we cannot back up.
 */
class SelfHostedFontsAndClaimsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_load_fonts_from_our_own_site_only(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        foreach ([$this->get('/login'), $this->get('/register'), $this->actingAs($user)->get('/profile')] as $res) {
            $html = $res->assertOk()->getContent();
            $this->assertStringContainsString('/css/fonts.css', $html);
            $this->assertStringNotContainsString('fonts.bunny.net', $html);
            $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        }
    }

    public function test_every_font_file_the_stylesheet_names_exists(): void
    {
        $css = file_get_contents(public_path('css/fonts.css'));
        preg_match_all("#url\\('(/fonts/[^']+)'\\)#", $css, $m);

        $this->assertCount(11, $m[1]);
        // A "*/" inside the header comment once ended it early and broke the first rule:
        // with the comments removed, nothing but whitespace may come before it.
        $before = substr($css, 0, strpos($css, '@font-face'));
        $this->assertSame('', trim(preg_replace('#/\*.*?\*/#s', '', $before)), 'stray text before the first @font-face');
        foreach ($m[1] as $path) {
            $this->assertFileExists(public_path(ltrim($path, '/')), $path);
        }
    }

    public function test_the_side_panel_makes_no_unsupported_claims(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        foreach (["#1 AgriTech", '24/7', '36+'] as $claim) {
            $this->assertStringNotContainsString($claim, $html, "side panel still says {$claim}");
        }
        $this->assertStringContainsString('14 Role Dashboards', $html);
    }

    public function test_the_security_policy_still_lists_the_same_font_sources(): void
    {
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("font-src 'self' data: https://fonts.bunny.net https://cdnjs.cloudflare.com", $csp);
    }
}
