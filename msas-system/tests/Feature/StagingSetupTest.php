<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SubscriptionLimitService;
use Database\Seeders\StagingQaAccountsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 12.2 staging: QA accounts only on staging, passwords only from
 * secrets and never printed, scans for QA accounts only, test-mode payments
 * only, and no CEO account with a password from the public repo.
 */
class StagingSetupTest extends TestCase
{
    use RefreshDatabase;

    private array $env = [];

    private function setEnv(string $k, ?string $v): void
    {
        $this->env[$k] ??= getenv($k);
        $v === null ? putenv($k) : putenv("{$k}={$v}");
    }

    protected function tearDown(): void
    {
        foreach ($this->env as $k => $v) {
            $v === false ? putenv($k) : putenv("{$k}={$v}");
        }
        parent::tearDown();
    }

    private function secrets(): array
    {
        $s = [];
        foreach (array_keys(StagingQaAccountsSeeder::ACCOUNTS) as $k) {
            $s[$k] = 'test-secret-' . $k . '-' . bin2hex(random_bytes(8));
            $this->setEnv("QA_{$k}_PASSWORD", $s[$k]);
        }
        return $s;
    }

    public function test_the_qa_seeder_does_nothing_outside_staging(): void
    {
        $this->secrets();
        foreach (['production', 'local', 'testing'] as $env) {
            $this->app['env'] = $env;
            // Called directly: db:seed itself would stop at its production prompt.
            (new StagingQaAccountsSeeder())->run();
            $this->assertSame(0, User::where('email', 'like', 'qa-%@staging.msasagro.com')->count(), $env);
        }
    }

    public function test_on_staging_it_creates_the_four_accounts_from_secrets_and_never_prints_a_password(): void
    {
        $secrets = $this->secrets();
        $this->app['env'] = 'staging';
        Artisan::call('db:seed', ['--class' => StagingQaAccountsSeeder::class, '--force' => true]);
        $out = Artisan::output();

        foreach (StagingQaAccountsSeeder::ACCOUNTS as $key => $spec) {
            $u = User::where('email', $spec['email'])->firstOrFail();
            $this->assertSame($spec['role'], $u->role);
            $this->assertTrue((bool) $u->is_qa);
            $this->assertTrue(Hash::check($secrets[$key], $u->password));
            $this->assertStringNotContainsString($secrets[$key], $out, "{$key} password printed");
        }
        $this->assertSame(['farmer', 'agro-dealer', 'extension-officer', 'admin'],
            array_column(StagingQaAccountsSeeder::ACCOUNTS, 'role'));

        // Idempotent; a rotated secret changes the password on the next deploy.
        $this->setEnv('QA_FARMER_PASSWORD', 'rotated-secret-0123456789');
        Artisan::call('db:seed', ['--class' => StagingQaAccountsSeeder::class, '--force' => true]);
        $this->assertSame(1, User::where('email', 'qa-farmer@staging.msasagro.com')->count());
        $this->assertTrue(Hash::check('rotated-secret-0123456789', User::where('email', 'qa-farmer@staging.msasagro.com')->value('password')));
    }

    public function test_an_account_without_its_secret_is_skipped(): void
    {
        $this->secrets();
        $this->setEnv('QA_ADMIN_PASSWORD', 'short');
        $this->app['env'] = 'staging';
        $this->seed(StagingQaAccountsSeeder::class);
        $this->assertNull(User::where('email', 'qa-admin@staging.msasagro.com')->first());
        $this->assertNotNull(User::where('email', 'qa-farmer@staging.msasagro.com')->first());
    }

    public function test_on_staging_only_qa_accounts_can_scan(): void
    {
        $qa = User::factory()->create(['role' => 'farmer']);
        $qa->forceFill(['is_qa' => true])->save();
        $other = User::factory()->create(['role' => 'farmer']);
        $svc = app(SubscriptionLimitService::class);

        config(['qa.enabled' => true, 'qa.scans_qa_only' => true]);
        $this->assertTrue($svc->canScan($qa)['allowed']);
        $this->assertFalse($svc->canScan($other)['allowed']);
        $this->assertSame('staging_qa_only', $svc->canScan($other)['plan']);

        config(['qa.scans_qa_only' => false]);
        $this->assertTrue($svc->canScan($other)['allowed'], 'production behaviour unchanged when the flag is off');
    }

    public function test_qa_only_scans_can_never_be_on_in_production(): void
    {
        $this->setEnv('APP_ENV', 'production');
        $this->setEnv('QA_SCANS_QA_ONLY', 'true');
        $this->assertFalse((require config_path('qa.php'))['scans_qa_only']);
    }

    public function test_live_paystack_keys_are_dropped_outside_production(): void
    {
        config(['services.paystack.secret_key' => 'sk_live_abc', 'services.paystack.public_key' => 'pk_live_abc']);
        (new \App\Providers\AppServiceProvider($this->app))->boot();
        $this->assertNull(config('services.paystack.secret_key'));
        $this->assertNull(config('services.paystack.public_key'));

        config(['services.paystack.secret_key' => 'sk_test_abc', 'services.paystack.public_key' => 'pk_test_abc']);
        (new \App\Providers\AppServiceProvider($this->app))->boot();
        $this->assertSame('sk_test_abc', config('services.paystack.secret_key'));
    }

    public function test_the_ceo_account_is_never_created_with_a_password_from_the_repo(): void
    {
        $this->setEnv('SUPER_ADMIN_PASSWORD', null);
        $this->seed(SuperAdminSeeder::class);
        $this->assertNull(User::where('email', 'msaslivestockagroservices@gmail.com')->first());

        $this->setEnv('SUPER_ADMIN_PASSWORD', 'a-long-secret-from-render');
        $this->seed(SuperAdminSeeder::class);
        $ceo = User::where('email', 'msaslivestockagroservices@gmail.com')->firstOrFail();
        $this->assertTrue(Hash::check('a-long-secret-from-render', $ceo->password));
        $this->assertFalse(Hash::check('Admin@12345', $ceo->password));
    }
}
