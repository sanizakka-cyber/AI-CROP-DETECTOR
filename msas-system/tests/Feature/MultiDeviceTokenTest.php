<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Per-device API tokens: signing in on a second device no longer signs the
 * first one out; signing out affects only that device; password changes
 * still sign out every device. Asserted by whether the token actually
 * authenticates, not just by column values.
 */
class MultiDeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    private function me(string $token)
    {
        $this->app['auth']->forgetGuards();
        return $this->withHeaders(['Authorization' => "Bearer {$token}"])->getJson('/api/auth/me');
    }

    public function test_signing_in_on_a_second_device_keeps_the_first_signed_in(): void
    {
        $user = User::factory()->create();
        $phone  = $user->createToken('phone')->plainTextToken;
        $tablet = $user->createToken('tablet')->plainTextToken;

        $this->me($phone)->assertOk();
        $this->me($tablet)->assertOk();
    }

    public function test_logout_signs_out_only_that_device(): void
    {
        $user = User::factory()->create();
        $phone  = $user->createToken('phone')->plainTextToken;
        $tablet = $user->createToken('tablet')->plainTextToken;

        $this->withHeaders(['Authorization' => "Bearer {$phone}"])->postJson('/api/auth/logout')->assertOk();

        $this->me($phone)->assertStatus(401);
        $this->me($tablet)->assertOk();
    }

    public function test_password_change_signs_out_every_device(): void
    {
        $user = User::factory()->create(['password' => bcrypt('OldPassw0rd!23')]);
        $phone  = $user->createToken('phone')->plainTextToken;
        $tablet = $user->createToken('tablet')->plainTextToken;

        $this->actingAs($user)->put('/password', [
            'current_password' => 'OldPassw0rd!23',
            'password' => 'NewPassw0rd!23', 'password_confirmation' => 'NewPassw0rd!23',
        ]);

        $this->me($phone)->assertStatus(401);
        $this->me($tablet)->assertStatus(401);
        $this->assertSame(0, ApiToken::where('user_id', $user->id)->count());
    }

    public function test_a_deactivated_user_is_rejected_on_every_device(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phone')->plainTextToken;
        $user->forceFill(['is_active' => false])->save();

        $this->me($token)->assertStatus(401);
    }

    public function test_a_token_issued_before_per_device_tokens_still_works(): void
    {
        $user = User::factory()->create();
        $legacy = bin2hex(random_bytes(32));
        $user->forceFill(['api_token' => hash('sha256', $legacy)])->saveQuietly();

        $this->me($legacy)->assertOk();
    }

    public function test_only_the_newest_ten_devices_are_kept(): void
    {
        $user = User::factory()->create();
        $first = $user->createToken('d0')->plainTextToken;
        for ($i = 1; $i <= User::MAX_API_TOKENS; $i++) {
            $user->createToken("d{$i}");
        }

        $this->assertSame(User::MAX_API_TOKENS, ApiToken::where('user_id', $user->id)->count());
        $this->me($first)->assertStatus(401);
    }
}
