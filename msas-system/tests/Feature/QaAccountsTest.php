<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SubscriptionLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 12.1: dedicated QA accounts with a QA-only scan quota, which can
 * never be switched on in production, and whose password never passes
 * through the command line or the console output.
 */
class QaAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_qa_account_gets_the_qa_quota_only_when_qa_is_enabled(): void
    {
        $qa = User::factory()->create(['role' => 'farmer']);
        $qa->forceFill(['is_qa' => true])->save();
        $farmer = User::factory()->create(['role' => 'farmer']);
        $svc = app(SubscriptionLimitService::class);

        config(['qa.enabled' => true, 'qa.scan_limit' => 200]);
        $this->assertSame(200, $svc->canScan($qa)['limit']);
        $this->assertSame('qa', $svc->canScan($qa)['plan']);
        $this->assertSame(5, $svc->canScan($farmer)['limit'], 'ordinary accounts are unaffected');

        config(['qa.enabled' => false]);
        $this->assertSame(5, $svc->canScan($qa)['limit']);
    }

    public function test_qa_can_never_be_enabled_in_production(): void
    {
        $saved = [getenv('APP_ENV'), getenv('QA_ACCOUNTS_ENABLED')];
        try {
            putenv('APP_ENV=production');
            putenv('QA_ACCOUNTS_ENABLED=true');
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'production';
            $_ENV['QA_ACCOUNTS_ENABLED'] = $_SERVER['QA_ACCOUNTS_ENABLED'] = 'true';
            $this->assertFalse((require config_path('qa.php'))['enabled']);

            putenv('APP_ENV=staging');
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'staging';
            $this->assertTrue((require config_path('qa.php'))['enabled'], 'staging can switch it on');
        } finally {
            putenv('APP_ENV' . ($saved[0] === false ? '' : '=' . $saved[0]));
            putenv('QA_ACCOUNTS_ENABLED' . ($saved[1] === false ? '' : '=' . $saved[1]));
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $saved[0] ?: 'testing';
            unset($_ENV['QA_ACCOUNTS_ENABLED'], $_SERVER['QA_ACCOUNTS_ENABLED']);
        }
    }

    public function test_the_command_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        putenv('QA_ACCOUNT_PASSWORD=a-long-staging-password');
        try {
            $this->artisan('qa:create-account', ['email' => 'qa@msasagro.test'])
                ->expectsOutput('QA accounts cannot be created in production.')
                ->assertExitCode(1);
        } finally {
            putenv('QA_ACCOUNT_PASSWORD');
        }
        $this->assertSame(0, User::where('email', 'qa@msasagro.test')->count());
    }

    public function test_the_command_creates_a_qa_account_without_showing_the_password(): void
    {
        config(['qa.enabled' => true]);
        $secret = 'staging-' . bin2hex(random_bytes(8));
        putenv("QA_ACCOUNT_PASSWORD={$secret}");
        try {
            $this->artisan('qa:create-account', ['email' => 'QA-Farmer@msasagro.test', '--role' => 'farmer'])
                ->doesntExpectOutputToContain($secret)
                ->assertExitCode(0);
        } finally {
            putenv('QA_ACCOUNT_PASSWORD');
        }
        $u = User::where('email', 'qa-farmer@msasagro.test')->sole();
        $this->assertTrue($u->is_qa);
        $this->assertTrue(Hash::check($secret, $u->password));
    }

    public function test_the_command_needs_a_password_from_the_environment(): void
    {
        putenv('QA_ACCOUNT_PASSWORD');
        $this->artisan('qa:create-account', ['email' => 'qa2@msasagro.test'])->assertExitCode(1);
        $this->assertSame(0, User::where('email', 'qa2@msasagro.test')->count());
    }

    public function test_users_cannot_make_themselves_qa(): void
    {
        $u = User::factory()->create(['role' => 'farmer']);
        $this->withHeaders(['Authorization' => 'Bearer ' . $u->createToken('t')->plainTextToken])
            ->patchJson('/api/auth/profile', ['is_qa' => true, 'first_name' => 'Ade']);
        $this->assertFalse((bool) $u->fresh()->is_qa);
    }
}
