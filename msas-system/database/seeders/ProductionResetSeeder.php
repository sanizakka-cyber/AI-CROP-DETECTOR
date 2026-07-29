<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| ProductionResetSeeder — Clean Slate for Production UAT
|--------------------------------------------------------------------------
| Deletes ALL user accounts except the CEO, along with all associated
| test data. System configuration, product catalog, categories, and
| subscription plan definitions are preserved.
|
| ONLY the CEO account is preserved:
|   msaslivestockagroservices@gmail.com
|
| After running, all users register fresh through the live workflow.
|
| Run:
|   php artisan db:seed --class=ProductionResetSeeder
|
| This action is IRREVERSIBLE. Take a database backup first.
|--------------------------------------------------------------------------
*/

class ProductionResetSeeder extends Seeder
{
    const CEO_EMAIL = 'msaslivestockagroservices@gmail.com';

    public function run(): void
    {
        // Double-confirm before proceeding — destructive operation
        if (!$this->command->confirm(
            "\n⚠️  This will permanently DELETE all users except the CEO (" . self::CEO_EMAIL . ").\n   All test data will be removed. This CANNOT be undone.\n   Continue?",
            false
        )) {
            $this->command->warn('Reset aborted. No changes made.');
            return;
        }

        // Verify CEO account exists before touching anything
        $ceo = User::where('email', self::CEO_EMAIL)->first();
        if (!$ceo) {
            $this->command->error('CEO account not found at: ' . self::CEO_EMAIL);
            $this->command->error('Aborting — will not delete users without a valid CEO account to preserve.');
            return;
        }

        $ceoId = $ceo->id;
        $this->command->info("CEO account confirmed: ID {$ceoId} — will be preserved.");

        // Collect all non-CEO user IDs
        $otherIds = User::where('id', '!=', $ceoId)->pluck('id');
        $totalUsers = $otherIds->count();

        if ($totalUsers === 0) {
            $this->command->info('No other users found. Nothing to delete.');
            $this->ensureCeoIsClean($ceo);
            return;
        }

        $this->command->info("Preparing to remove {$totalUsers} user accounts and associated data...");

        // Collect identifiers for OTP lookup before deletion
        $otherEmails = User::whereIn('id', $otherIds)->whereNotNull('email')->pluck('email');
        $otherPhones = User::whereIn('id', $otherIds)->whereNotNull('phone')->pluck('phone');

        DB::transaction(function () use ($otherIds, $otherEmails, $otherPhones, $totalUsers) {
            $this->purge('diagnosis_feedbacks', 'user_id', $otherIds);
            $this->purge('diagnoses',           'user_id', $otherIds);

            $this->purge('vaccinations',    'animal_id', DB::table('animals')->whereIn('user_id', $otherIds)->pluck('id'));
            $this->purge('egg_productions', 'poultry_record_id', DB::table('poultry_records')->whereIn('user_id', $otherIds)->pluck('id'));
            $this->purge('animals',         'user_id', $otherIds);
            $this->purge('poultry_records', 'user_id', $otherIds);
            $this->purge('farm_records',    'user_id', $otherIds);
            $this->purge('sales',           'user_id', $otherIds);
            $this->purge('finances',        'user_id', $otherIds);

            // Orders and line items
            $orderIds = $this->pluckFrom('orders', 'id', 'buyer_id', $otherIds);
            $this->purge('order_items', 'order_id', $orderIds);
            $this->purge('orders',      'buyer_id', $otherIds);

            // Products (dealer listings) and related
            $productIds = $this->pluckFrom('products', 'id', 'dealer_id', $otherIds);
            $this->purge('product_reviews', 'product_id', $productIds);
            $this->purge('cart_items',      'product_id', $productIds);
            $this->purge('order_items',     'product_id', $productIds);   // already deleted above, safe to re-run
            $this->purge('products',        'dealer_id',  $otherIds);

            // Cart items belonging to deleted users (any remaining)
            $this->purge('cart_items', 'user_id', $otherIds);

            // Marketplace items
            $this->purge('marketplace_items', 'user_id', $otherIds);

            // Consultations (farmer + expert both may be non-CEO)
            $this->purge('consultations', 'farmer_id', $otherIds);

            // Subscriptions and usage
            $this->purge('subscription_usages', 'user_id', $otherIds);
            $this->purge('subscriptions',        'user_id', $otherIds);

            // Payments
            $this->purge('payments', 'user_id', $otherIds);

            // Wallet and transactions
            $walletIds = $this->pluckFrom('wallets', 'id', 'user_id', $otherIds);
            $this->purge('wallet_transactions', 'wallet_id', $walletIds);
            $this->purge('wallets',             'user_id',   $otherIds);

            // Notifications
            $this->purge('notifications', 'user_id', $otherIds);

            // Messages (sender or recipient)
            if (Schema::hasTable('messages')) {
                DB::table('messages')
                    ->where(function ($q) use ($otherIds) {
                        $q->whereIn('sender_id', $otherIds)
                          ->orWhereIn('receiver_id', $otherIds);
                    })
                    ->delete();
            }

            // Support tickets
            $ticketIds = $this->pluckFrom('support_tickets', 'id', 'user_id', $otherIds);
            $this->purge('support_ticket_messages', 'ticket_id', $ticketIds);
            $this->purge('support_tickets',          'user_id',  $otherIds);

            // Extension records
            $this->purge('extension_advisory', 'farmer_id',  $otherIds);
            $this->purge('extension_advisory', 'officer_id', $otherIds);
            $this->purge('extension_visits',   'farmer_id',  $otherIds);
            $this->purge('extension_visits',   'officer_id', $otherIds);

            // Operations tasks
            $this->purge('operations_tasks', 'created_by',  $otherIds);
            $this->purge('operations_tasks', 'assigned_to', $otherIds);

            // HR tables
            $this->purge('payrolls',      'user_id', $otherIds);
            $this->purge('attendances',   'user_id', $otherIds);
            $this->purge('leave_requests','user_id', $otherIds);

            // Referrals and feedback
            $this->purge('referrals', 'user_id',    $otherIds);
            $this->purge('referrals', 'referred_id', $otherIds);
            $this->purge('feedbacks', 'user_id',    $otherIds);
            $this->purge('feedback',  'user_id',    $otherIds);

            // RBAC role assignments
            $this->purge('staff_role_assignments', 'user_id', $otherIds);

            // User documents
            $this->purge('user_documents', 'user_id', $otherIds);

            // Login history
            $this->purge('login_histories', 'user_id', $otherIds);

            // OTPs (keyed by identifier string, not FK)
            if (Schema::hasTable('otps')) {
                $identifiers = $otherEmails->merge($otherPhones)->unique()->values();
                if ($identifiers->isNotEmpty()) {
                    DB::table('otps')->whereIn('identifier', $identifiers)->delete();
                }
            }
            if (Schema::hasTable('otp_delivery_logs')) {
                $identifiers = $otherEmails->merge($otherPhones)->unique()->values();
                if ($identifiers->isNotEmpty()) {
                    DB::table('otp_delivery_logs')->whereIn('identifier', $identifiers)->delete();
                }
            }

            // Sessions (DB-based sessions table)
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->whereIn('user_id', $otherIds)->delete();
            }

            // Finally delete the users themselves
            User::whereIn('id', $otherIds)->delete();

            $this->command->info("Deleted {$totalUsers} user accounts and all associated records.");
        });

        // Clean up CEO's UAT test flags now that testing is done
        $this->ensureCeoIsClean(User::find($ceo->id));

        // Final verification
        $remaining = User::count();
        $this->command->info("\n✓ Reset complete.");
        $this->command->info("Users remaining in database: {$remaining}");
        if ($remaining === 1) {
            $ceoFresh = User::first();
            $this->command->info("Preserved: {$ceoFresh->email} (role: {$ceoFresh->role})");
        } else {
            $this->command->warn("Expected 1 user, found {$remaining}. Please verify.");
        }
        $this->command->info("The platform is ready for fresh registration-based UAT.");
    }

    // Ensure the CEO account is clean: active, verified, role=ceo, no test flags
    private function ensureCeoIsClean(User $ceo): void
    {
        $ceo->update([
            'role'               => 'ceo',
            'is_active'          => true,
            'email_verified_at'  => $ceo->email_verified_at ?? now(),
            'notification_email' => null,   // receive emails at real address, not UAT redirect
            'force_password_reset' => false,
        ]);
        $ceo->is_test_account = false;
        $ceo->save();
        $this->command->info("CEO account verified: active, email verified, no test flags.");
    }

    // Safely delete rows from a table if the table exists and the ID list is non-empty
    private function purge(string $table, string $column, $ids): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        $ids = collect($ids)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return;
        }
        DB::table($table)->whereIn($column, $ids)->delete();
    }

    // Fetch IDs from a table matching a where condition (used to get child IDs before purging)
    private function pluckFrom(string $table, string $select, string $where, $ids): \Illuminate\Support\Collection
    {
        if (!Schema::hasTable($table)) {
            return collect();
        }
        $ids = collect($ids)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }
        return DB::table($table)->whereIn($where, $ids)->pluck($select);
    }
}
