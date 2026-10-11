<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Plain string, not enum() -- two later migrations
            // (2026_07_21_200001, 2026_07_24_000001) already grew this
            // value set past the original three plans by dropping and
            // recreating a Postgres CHECK constraint, but both skip
            // SQLite entirely ("SQLite doesn't enforce this at the DB
            // level anyway" -- it does; same incorrect assumption fixed
            // for support_tickets.priority in 21b83ea). So on a fresh
            // SQLite test database the original ['basic','pro','premium']
            // constraint never grows, and registering any non-farmer,
            // non-general-user role -- which starts a trial on
            // 'professional_starter' via User::startTrial() -- fails with
            // a CHECK violation that doesn't exist on production
            // Postgres, where the later migrations did take effect. The
            // real, validated plan vocabulary already lives in
            // config/subscription.php and is enforced at the application
            // layer (RegistrationService::createAccount()), so the DB
            // constraint is a redundant, drifting duplicate -- removed at
            // the source instead of patched a third time.
            $table->string('plan', 30)->default('basic');
            $table->enum('status', ['trial', 'active', 'expired', 'cancelled', 'suspended'])->default('trial');
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('upgraded_at')->nullable();
            $table->string('upgraded_from')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('payment_reference')->nullable()->unique();
            $table->string('payment_method')->nullable(); // paystack, flutterwave, bank_transfer, manual
            $table->boolean('auto_renew')->default(true);
            $table->string('cancellation_reason')->nullable();
            $table->text('notes')->nullable(); // admin notes
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete(); // admin who manually activated
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
