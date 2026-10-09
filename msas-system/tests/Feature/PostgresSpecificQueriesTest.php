<?php

namespace Tests\Feature;

use App\Models\Consultation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 7, Section 3: roughly 20 controllers use Postgres-only raw SQL
 * ('ilike' for case-insensitive search, 'EXTRACT(EPOCH FROM ...)' for
 * duration/response-time metrics) -- intentionally, since production is
 * always Postgres (render.yaml). Rewriting ~20 files of correct,
 * parameterized, injection-safe filters into a portable form just to run
 * on SQLite would be a disproportionate refactor for code that already
 * works correctly against the database it was written for.
 *
 * These tests instead prove the Postgres-specific behavior is correct
 * against a REAL PostgreSQL instance (see .github/workflows/
 * postgres-tests.yml) -- two representative 'ilike' search filters
 * (case-insensitive substring match is the identical, simple pattern
 * repeated across all ~16 occurrences) and both 'EXTRACT(EPOCH FROM...)'
 * duration computations (verified against a known, exact time delta, not
 * just "doesn't crash").
 *
 * Every test here skips itself (not a false pass, an explicit skip) when
 * run against SQLite, so the existing SQLite-based workflow
 * (security-regression-tests.yml) stays a meaningful gate for everything
 * else rather than being broken by SQL this database cannot run at all.
 */
class PostgresSpecificQueriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires a real PostgreSQL connection — see .github/workflows/postgres-tests.yml. SQLite has no ILIKE/EXTRACT(EPOCH) support.');
        }
    }

    // ── 'ilike' case-insensitive search ─────────────────────────────────────────

    public function test_admin_user_search_is_case_insensitive_via_ilike(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['first_name' => 'Chidinma', 'last_name' => 'Okafor']);
        User::factory()->create(['first_name' => 'Musa', 'last_name' => 'Bello']);

        // Mixed-case search term against a lower-case stored value --
        // the exact behavior 'ilike' exists to provide (AdminController::users()).
        $response = $this->actingAs($admin)->get('/admin/users?search=CHIDINMA');

        $response->assertOk();
        $response->assertSee('Chidinma');
        $response->assertDontSee('Musa Bello');
    }

    public function test_marketplace_product_search_is_case_insensitive_via_ilike(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $dealer = User::factory()->create(['role' => 'agro-dealer']);

        Product::create([
            'dealer_id' => $dealer->id, 'seller_type' => 'dealer', 'name' => 'NPK Fertilizer 20-10-10',
            'category' => 'fertilizer', 'unit' => 'bag', 'cost_price' => 5000, 'selling_price' => 6000,
            'quantity_in_stock' => 50, 'low_stock_threshold' => 5, 'status' => 'active', 'is_approved' => true,
        ]);
        Product::create([
            'dealer_id' => $dealer->id, 'seller_type' => 'dealer', 'name' => 'Cutlass',
            'category' => 'tools', 'unit' => 'piece', 'cost_price' => 1000, 'selling_price' => 1500,
            'quantity_in_stock' => 20, 'low_stock_threshold' => 2, 'status' => 'active', 'is_approved' => true,
        ]);

        // Lower-case search term against a mixed-case stored name (MarketplaceController::index()).
        $response = $this->actingAs($farmer)->get('/marketplace?search=fertilizer');

        $response->assertOk();
        $response->assertSee('NPK Fertilizer');
        $response->assertDontSee('Cutlass');
    }

    // ── EXTRACT(EPOCH FROM ...) duration computations ───────────────────────────

    public function test_consultation_average_completion_hours_is_computed_correctly(): void
    {
        $farmer = User::factory()->create(['role' => 'farmer']);
        $vet = User::factory()->create(['role' => 'vet']);

        // Exactly 6 hours between creation and completion.
        $consultation = Consultation::create([
            'farmer_id' => $farmer->id, 'expert_id' => $vet->id, 'case_type' => 'livestock',
            'status' => 'resolved',
        ]);
        $consultation->forceFill([
            'created_at'   => now()->subHours(10),
            'completed_at' => now()->subHours(4),
        ])->save();

        // The exact expression CEOController::consultStatsMetrics() uses.
        $avgHours = (float) Consultation::whereNotNull('completed_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at)) / 3600) as avg_h')
            ->value('avg_h');

        $this->assertEqualsWithDelta(6.0, $avgHours, 0.05);
    }

    public function test_support_ticket_sla_window_is_computed_correctly(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);

        // Resolved well within 24h (12h) -- must count as SLA-compliant.
        $withinSla = DB::table('support_tickets')->insertGetId([
            'user_id' => $user->id, 'ticket_number' => 'TCK-0001', 'subject' => 'Within SLA', 'message' => 'x',
            'status' => 'resolved', 'created_at' => now()->subHours(12), 'updated_at' => now(),
        ]);
        // Resolved outside 24h (48h) -- must NOT count as SLA-compliant.
        DB::table('support_tickets')->insert([
            'user_id' => $user->id, 'ticket_number' => 'TCK-0002', 'subject' => 'Breached SLA', 'message' => 'x',
            'status' => 'resolved', 'created_at' => now()->subHours(72), 'updated_at' => now()->subHours(24),
        ]);

        // The exact expression DashboardController::customerSupport() uses.
        $withinCount = DB::table('support_tickets')->where('status', 'resolved')
            ->whereRaw('EXTRACT(EPOCH FROM (updated_at::timestamp - created_at::timestamp)) / 3600 <= 24')
            ->count();

        $this->assertSame(1, $withinCount);
        $this->assertSame((int) $withinSla, DB::table('support_tickets')
            ->where('status', 'resolved')
            ->whereRaw('EXTRACT(EPOCH FROM (updated_at::timestamp - created_at::timestamp)) / 3600 <= 24')
            ->value('id'));
    }
}
