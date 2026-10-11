<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7, Section 8 audit, financially-sensitive cluster: the mobile
 * withdrawal endpoint (Api\WalletApiController::requestWithdrawal) called
 * WalletService::debit() directly -- an immediate, completed debit with
 * no admin review and no linkage to an actual bank payout -- instead of
 * WalletService::requestWithdrawal(), which the web WalletController
 * correctly uses to place funds in locked_balance pending
 * Admin\WalletManagementController::approveWithdrawal()/rejectWithdrawal().
 * Mobile users' balances were debited instantly with zero reconciliation
 * trail.
 */
class WalletWithdrawalApprovalHoldTest extends TestCase
{
    use RefreshDatabase;

    private function apiHeaders(User $user): array
    {
        $token = $user->createToken('test')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_requesting_a_mobile_withdrawal_locks_funds_instead_of_debiting_immediately(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        app(WalletService::class)->credit($user, 5000, 'Test funding');

        $response = $this->withHeaders($this->apiHeaders($user))->postJson('/api/wallet/withdraw', [
            'amount'         => 2000,
            'account_number' => '0123456789',
            'bank_name'      => 'Test Bank',
            'account_name'   => 'Test Farmer',
        ]);

        $response->assertOk();
        $wallet = $user->refresh()->wallet;
        $this->assertEquals(5000, $wallet->balance, 'The real balance must not drop until an admin approves the withdrawal.');
        $this->assertEquals(2000, $wallet->locked_balance, 'The requested amount must be held, not debited.');
        $this->assertEquals(3000, $wallet->available_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id, 'type' => 'hold', 'status' => 'pending', 'amount' => 2000,
        ]);
    }

    public function test_an_admin_approving_the_withdrawal_then_actually_debits_the_balance(): void
    {
        $user = User::factory()->create(['role' => 'farmer']);
        app(WalletService::class)->credit($user, 5000, 'Test funding');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->withHeaders($this->apiHeaders($user))->postJson('/api/wallet/withdraw', [
            'amount' => 2000, 'account_number' => '0123456789', 'bank_name' => 'Test Bank', 'account_name' => 'Test Farmer',
        ]);
        $txn = $user->refresh()->wallet->transactions()->where('type', 'hold')->first();

        $response = $this->actingAs($admin)->post(route('admin.wallets.withdrawals.approve', $txn));

        $response->assertRedirect();
        $wallet = $user->refresh()->wallet;
        $this->assertEquals(3000, $wallet->balance, 'Approval must finalize the debit.');
        $this->assertEquals(0, $wallet->locked_balance);
    }
}
