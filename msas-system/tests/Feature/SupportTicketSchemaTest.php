<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 7, Section 3/9: two migrations both target 'support_tickets', each
 * guarded by `if (!Schema::hasTable('support_tickets'))`:
 *   2026_07_06_000001_create_support_operations_extension_tables.php
 *     (earlier timestamp -- runs first, wins) creates id/user_id/
 *     assigned_to/subject/category/priority/status/description/
 *     reference/resolved_at/timestamps. No ticket_number, message, or
 *     resolution column.
 *   2026_07_28_700001_create_support_tickets_table.php (later) creates
 *     id/user_id/ticket_number/subject/message/category/priority/
 *     status/assigned_to/resolution/resolved_at/timestamps -- but its
 *     own Schema::create is a no-op on a fresh install, since the table
 *     already exists from the July 6 migration.
 * App\Models\SupportTicket + SupportTicketController::store() (the real,
 * currently-routed farmer "submit a support ticket" flow) assume the
 * JULY 28 shape: they write ticket_number, message, and the controller
 * calls SupportTicket::generateNumber(). No test existed for this
 * controller before this phase. This test reproduces the real flow
 * directly to get a definitive, evidence-based answer instead of
 * inferring one from reading the migration files alone.
 */
class SupportTicketSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_tickets_table_has_the_columns_the_real_app_code_writes(): void
    {
        // Documents the exact columns SupportTicketController::store() and
        // SupportTicket::generateNumber() require to exist, independent of
        // whether submitting a ticket end-to-end happens to throw.
        $this->assertTrue(Schema::hasTable('support_tickets'));
        foreach (['ticket_number', 'message', 'resolution'] as $column) {
            $this->assertTrue(
                Schema::hasColumn('support_tickets', $column),
                "support_tickets.{$column} is missing on a fresh migration run -- ".
                "App\\Models\\SupportTicket and SupportTicketController::store() both require it."
            );
        }
    }

    public function test_a_farmer_can_submit_a_support_ticket(): void
    {
        $this->withoutExceptionHandling(); // TEMP: surface the real exception instead of a swallowed 500
        $farmer = User::factory()->create(['role' => 'farmer']);

        $response = $this->actingAs($farmer)->post(route('support.store'), [
            'subject'  => 'My scan results look wrong',
            'message'  => 'The confidence score seems too low for a clearly healthy plant.',
            'category' => 'technical',
            'priority' => 'normal',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseCount('support_tickets', 1);
    }
}
