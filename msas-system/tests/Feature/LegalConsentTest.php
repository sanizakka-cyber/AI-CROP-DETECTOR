<?php

namespace Tests\Feature;

use App\Models\LegalAcceptance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Terms/Privacy agreement and the 18+ rule at registration (web and app),
 * re-acceptance of new versions, and the "subscription payments are
 * non-refundable" acknowledgement before payment (web and app).
 */
class LegalConsentTest extends TestCase
{
    use RefreshDatabase;

    private function webPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'            => 'Amina',
            'last_name'             => 'Bello',
            'identifier'            => 'amina@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'farmer',
            'state'                 => 'Katsina',
            'age'                   => 30,
            'age_confirmed'         => '1',
            'terms_accepted'        => '1',
        ], $overrides);
    }

    private function apiPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'            => 'Musa',
            'last_name'             => 'Ali',
            'identifier'            => 'musa@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role'                  => 'farmer',
            'state'                 => 'Katsina',
            'age'                   => 40,
            'age_confirmed'         => true,
            'terms_accepted'        => true,
            'app_version'           => '1.4.11',
        ], $overrides);
    }

    // ── Web registration ────────────────────────────────────────────────

    public function test_web_registration_is_rejected_without_agreeing_to_the_terms(): void
    {
        Mail::fake();
        $this->post('/register', $this->webPayload(['terms_accepted' => null]))
            ->assertSessionHasErrors('terms_accepted');

        $this->assertDatabaseMissing('users', ['email' => 'amina@example.com']);
    }

    public function test_web_registration_under_18_is_refused_and_nothing_is_stored(): void
    {
        Mail::fake();
        $response = $this->post('/register', $this->webPayload(['age' => 15]));

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('age');
        $this->assertDatabaseMissing('users', ['email' => 'amina@example.com']);
        $this->assertSame(0, LegalAcceptance::count());

        // The entered name and contact details are not kept in the session.
        $old = session('_old_input', []);
        $this->assertArrayNotHasKey('first_name', $old);
        $this->assertArrayNotHasKey('identifier', $old);
        $this->assertArrayNotHasKey('age', $old);
    }

    public function test_web_registration_requires_age_and_the_18_plus_confirmation(): void
    {
        Mail::fake();
        $this->post('/register', $this->webPayload(['age' => null]))->assertSessionHasErrors('age');
        $this->post('/register', $this->webPayload(['age_confirmed' => null]))->assertSessionHasErrors('age_confirmed');
        $this->post('/register', $this->webPayload(['age' => 'thirty']))->assertSessionHasErrors('age');
        $this->assertDatabaseMissing('users', ['email' => 'amina@example.com']);
    }

    public function test_web_registration_at_18_records_the_agreement_and_age(): void
    {
        Mail::fake();
        $this->post('/register', $this->webPayload(['age' => 18]))->assertSessionHasNoErrors();

        $user = User::where('email', 'amina@example.com')->firstOrFail();
        $this->assertSame(18, $user->age_declared);
        $this->assertNotNull($user->age_declared_at);
        $this->assertNotNull($user->terms_accepted_at);
        $this->assertSame(config('legal.versions.terms'), $user->terms_version);
        $this->assertSame(config('legal.versions.privacy'), $user->privacy_version);

        $docs = LegalAcceptance::where('user_id', $user->id)->pluck('context', 'document')->all();
        $this->assertSame(['age' => 'registration_web', 'privacy' => 'registration_web', 'terms' => 'registration_web'], collect($docs)->sortKeys()->all());
    }

    public function test_registration_page_shows_age_field_and_a_named_terms_checkbox(): void
    {
        $html = $this->get('/register')->assertOk()->getContent();
        $this->assertStringContainsString('id="s2_age"', $html);
        $this->assertStringContainsString('name="terms_accepted"', $html);
        $this->assertStringContainsString('name="age_confirmed"', $html);
    }

    // ── App (API) registration ──────────────────────────────────────────

    public function test_api_registration_is_rejected_without_terms_or_age(): void
    {
        Mail::fake();
        $this->postJson('/api/auth/register', $this->apiPayload(['terms_accepted' => false]))
            ->assertStatus(422)->assertJsonValidationErrors('terms_accepted', 'details');
        $this->postJson('/api/auth/register', $this->apiPayload(['age' => null]))
            ->assertStatus(422)->assertJsonValidationErrors('age', 'details');
        $this->assertDatabaseMissing('users', ['email' => 'musa@example.com']);
    }

    public function test_api_registration_under_18_is_refused_with_a_clear_message(): void
    {
        Mail::fake();
        $response = $this->postJson('/api/auth/register', $this->apiPayload(['age' => 16]));

        $response->assertStatus(422)->assertJsonValidationErrors('age', 'details');
        $this->assertStringContainsString('18 or older', $response->json('details.age.0'));
        $this->assertDatabaseMissing('users', ['email' => 'musa@example.com']);
    }

    public function test_api_registration_records_the_agreement_with_app_version(): void
    {
        Mail::fake();
        $this->postJson('/api/auth/register', $this->apiPayload())->assertSuccessful();

        $user = User::where('email', 'musa@example.com')->firstOrFail();
        $this->assertSame(40, $user->age_declared);
        $this->assertSame(config('legal.versions.terms'), $user->terms_version);
        $this->assertTrue(LegalAcceptance::where([
            'user_id' => $user->id, 'document' => 'terms', 'context' => 'registration_app', 'app_version' => '1.4.11',
        ])->exists());
    }

    public function test_old_app_builds_can_register_only_while_api_consent_is_switched_off(): void
    {
        Mail::fake();
        config(['legal.require_api_consent' => false]);
        $payload = $this->apiPayload();
        unset($payload['age'], $payload['age_confirmed'], $payload['terms_accepted']);

        $this->postJson('/api/auth/register', $payload)->assertSuccessful();
        $user = User::where('email', 'musa@example.com')->firstOrFail();
        $this->assertNull($user->terms_version);

        // Even then, a declared age under 18 is still refused.
        $this->postJson('/api/auth/register', $this->apiPayload(['identifier' => 'kid@example.com', 'age' => 12]))
            ->assertStatus(422)->assertJsonValidationErrors('age', 'details');
    }

    // ── Re-acceptance of new versions (web) ─────────────────────────────

    public function test_existing_user_without_current_terms_is_asked_to_accept_once(): void
    {
        config(['legal.enforce_reacceptance' => true]);
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('legal.accept.show'));
        $this->actingAs($user)->get(route('legal.accept.show'))->assertOk()->assertSee('name="terms_accepted"', false);

        // Legal pages themselves stay readable.
        $this->actingAs($user)->get(route('legal.terms'))->assertOk();

        $this->actingAs($user)->post(route('legal.accept.store'), [])->assertSessionHasErrors('terms_accepted');
        $this->actingAs($user)->post(route('legal.accept.store'), ['terms_accepted' => '1'])->assertRedirect();

        $user->refresh();
        $this->assertSame(config('legal.versions.terms'), $user->terms_version);
        $this->assertTrue(LegalAcceptance::where(['user_id' => $user->id, 'context' => 'reacceptance_web'])->exists());
        $after = $this->actingAs($user)->get('/dashboard');
        $this->assertNotSame(route('legal.accept.show'), $after->headers->get('Location'));
    }

    public function test_a_version_bump_asks_again_but_json_and_post_requests_are_never_redirected(): void
    {
        config(['legal.enforce_reacceptance' => true]);
        $user = User::factory()->create([
            'role' => 'farmer', 'terms_version' => 'old', 'privacy_version' => config('legal.versions.privacy'),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('legal.accept.show'));
        $json = $this->actingAs($user)->getJson('/dashboard');
        $this->assertNotSame(route('legal.accept.show'), $json->headers->get('Location'));
        $post = $this->actingAs($user)->post(route('legal.accept.store'), []);
        $post->assertSessionHasErrors('terms_accepted'); // POSTs reach their controller
    }

    // ── No-refund acknowledgement before subscription payment ───────────

    private function fakePaystack(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_123', 'services.paystack.payment_url' => 'https://api.paystack.co']);
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
            'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc'],
        ])]);
    }

    public function test_web_subscription_payment_needs_the_no_refund_acknowledgement(): void
    {
        $this->fakePaystack();
        $user = User::factory()->create(['role' => 'farmer']);

        $this->actingAs($user)->post(route('subscription.subscribe'), ['plan' => 'basic', 'billing_cycle' => 'monthly'])
            ->assertSessionHasErrors('refund_acknowledged');
        Http::assertNothingSent();

        $this->actingAs($user)->post(route('subscription.subscribe'), [
            'plan' => 'basic', 'billing_cycle' => 'monthly', 'refund_acknowledged' => '1',
        ])->assertRedirect('https://checkout.paystack.com/abc');

        $ack = LegalAcceptance::where(['user_id' => $user->id, 'document' => 'refund'])->firstOrFail();
        $this->assertSame('subscription_web', $ack->context);
        $this->assertSame(config('legal.versions.refund'), $ack->version);
        $this->assertNotEmpty($ack->reference);
        Http::assertSent(fn ($r) => ($r['metadata']['refund_policy_version'] ?? null) === config('legal.versions.refund'));
    }

    public function test_plans_page_shows_the_no_refund_notice(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        $this->actingAs($user)->get(route('subscription.plans'))
            ->assertOk()
            ->assertSee('Subscription payments are non-refundable')
            ->assertSee('id="refund-ack"', false);
    }

    public function test_app_subscription_payment_needs_the_no_refund_acknowledgement(): void
    {
        $this->fakePaystack();
        $user = User::factory()->create(['role' => 'farmer']);
        $token = $user->createToken("test")->plainTextToken;

        $this->withToken($token)->postJson('/api/subscription/subscribe', ['plan' => 'basic', 'cycle' => 'monthly'])
            ->assertStatus(422)->assertJsonValidationErrors('refund_acknowledged', 'details');

        $this->withToken($token)->postJson('/api/subscription/subscribe', [
            'plan' => 'basic', 'cycle' => 'monthly', 'refund_acknowledged' => true,
        ])->assertOk();

        $this->assertTrue(LegalAcceptance::where([
            'user_id' => $user->id, 'document' => 'refund', 'context' => 'subscription_app',
        ])->exists());
    }
}
