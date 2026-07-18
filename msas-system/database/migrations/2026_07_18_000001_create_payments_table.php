<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('user_type')->nullable(); // farmer, vet, dealer, etc.

            // Transaction identifiers
            $table->string('transaction_id')->nullable()->unique(); // Paystack transaction ID
            $table->string('reference')->unique();                  // our generated reference

            // Amount
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('NGN');

            // Status & method
            $table->enum('status', ['pending', 'success', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable(); // card, bank_transfer, ussd, mobile_money

            // What was purchased
            $table->string('module');       // subscription, consultation, marketplace, logistics, training, etc.
            $table->unsignedBigInteger('module_id')->nullable(); // related record ID
            $table->string('description');

            // Paystack-specific
            $table->string('channel')->nullable();    // card, bank, ussd
            $table->string('gateway_response')->nullable();
            $table->json('metadata')->nullable();

            // Verification
            $table->enum('verification_status', ['unverified', 'verified', 'failed'])->default('unverified');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Receipt
            $table->string('receipt_number')->nullable()->unique();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['module', 'module_id']);
            $table->index('reference');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
