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
            $table->enum('plan', ['basic', 'pro', 'premium'])->default('basic');
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
