<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The landing page keeps every existing link, the language switcher and the
 * SEO tags, shows only real plan prices (from config/subscription.php), and
 * makes no invented claims (rankings, user counts, testimonials).
 */
class LandingPageTest extends TestCase
{
    private function page(): string
    {
        return $this->get('/')->assertOk()->getContent();
    }

    public function test_existing_links_are_kept(): void
    {
        $html = $this->page();

        foreach ([
            route('register'), route('login'), route('marketplace'),
            route('legal.privacy'), route('legal.terms'), route('legal.refund'),
            route('legal.cookie'), route('legal.faq'), route('legal.help'),
            route('register') . '?plan=basic', route('register') . '?plan=enterprise_plus',
            '#home', '#about', '#solutions', '#features', '#marketplace', '#pricing', '#contact',
            'tel:+2348032459879', 'tel:+2348129582957', 'mailto:sanizakka@gmail.com',
            'https://wa.me/2348129582957', 'https://wa.me/2348032459879',
            'https://maps.google.com?q=Dutsin+Safe+Lowcost+Katsina+Nigeria',
            'https://twitter.com/Sanizakka',
        ] as $href) {
            $this->assertStringContainsString('href="' . e($href) . '"', $html, "missing link {$href}");
        }
    }

    public function test_language_switcher_and_seo_tags_are_kept(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('action="' . route('locale.set') . '"', $html);
        foreach (['en', 'ha', 'fr', 'yo', 'ig'] as $code) {
            $this->assertStringContainsString('name="locale" value="' . $code . '"', $html);
        }

        foreach ([
            '<title>MSAS FarmAI | Smart Agriculture. Healthy Future.</title>',
            '<meta name="description"', '<meta property="og:title"', '<meta property="og:description"',
            '<meta property="og:url"', '<meta property="og:image"', '<meta property="og:type"',
            '<meta name="twitter:card"', '<meta name="twitter:title"', '<meta name="twitter:description"',
        ] as $tag) {
            $this->assertStringContainsString($tag, $html);
        }
    }

    public function test_prices_come_from_the_subscription_config(): void
    {
        $html = $this->page();

        foreach (['basic', 'basic_pro', 'premium', 'enterprise', 'enterprise_plus'] as $key) {
            $monthly = '₦' . number_format(config("subscription.plans.$key.price.monthly"));
            $this->assertStringContainsString($monthly, $html, "price missing for {$key}");
        }
    }

    public function test_no_invented_claims(): void
    {
        $html = strtolower($this->page());

        foreach ([
            "#1 agritech", "nigeria's leading", "nigeria&#039;s leading", "largest", '20,000+', '20k+', '150+',
            '100+ projects', '99.9%', 'testimonial', 'available on android &amp; ios', 'most popular',
        ] as $claim) {
            $this->assertStringNotContainsString($claim, $html, "invented claim on landing page: {$claim}");
        }
    }
}
