<?php

namespace Tests\Feature;

use Tests\TestCase;

/** The legal pages show their current version and the agreed rules. */
class LegalPagesRenderTest extends TestCase
{
    public function test_refund_policy_states_the_no_refund_rule_and_version(): void
    {
        $this->get(route('legal.refund'))->assertOk()
            ->assertSee('Subscription Payments Are Non-Refundable')
            ->assertSee(config('legal.versions.refund'))
            ->assertDontSee('Full refund, no questions asked');
    }

    public function test_terms_state_18_plus_and_link_the_refund_policy(): void
    {
        $this->get(route('legal.terms'))->assertOk()
            ->assertSee('18 years of age')
            ->assertSee(config('legal.versions.terms'))
            ->assertSee(route('legal.refund'), false);
    }

    public function test_privacy_policy_names_the_ai_provider_and_current_law(): void
    {
        $this->get(route('legal.privacy'))->assertOk()
            ->assertSee('Anthropic')
            ->assertSee('Nigeria Data Protection Act 2023')
            ->assertDontSee('NDPR Compliant')
            ->assertDontSee('not shared with third-party AI providers');
    }
}
