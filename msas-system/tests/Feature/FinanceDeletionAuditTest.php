<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Finance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit, admin/finance cluster:
 * FinanceController::deleteTransaction() permanently removed an
 * income/expense record with no audit trail -- unlike every other
 * destructive action in this controller family. Confirms the deletion
 * is now recorded before the row disappears.
 */
class FinanceDeletionAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_transaction_is_recorded_in_the_audit_log(): void
    {
        $finance = User::factory()->create(['role' => 'finance']);
        $owner   = User::factory()->create(['role' => 'farmer']);
        $txn = Finance::create([
            'user_id' => $owner->id, 'type' => 'Expense', 'category' => 'Feed',
            'amount' => 25000, 'transaction_date' => now()->toDateString(),
        ]);

        $this->actingAs($finance)
            ->delete(route('finance.transactions.delete', $txn))
            ->assertRedirect();

        $this->assertDatabaseMissing('finances', ['id' => $txn->id]);
        $log = AuditLog::where('action', 'finance.transaction_deleted')->where('model_id', $txn->id)->first();
        $this->assertNotNull($log, 'Deleting a finance record must leave an audit trail.');
        $this->assertSame($finance->id, $log->user_id);
        $this->assertStringContainsString('25000', $log->details);
    }
}
