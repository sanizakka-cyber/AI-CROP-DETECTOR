<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('locked_balance', 15, 2)->default(0); // pending withdrawals
            $table->string('currency', 3)->default('NGN');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['credit', 'debit', 'hold', 'release', 'refund']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('reference', 100)->unique();
            $table->string('description');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->json('metadata')->nullable(); // bank details, order refs, etc.
            $table->unsignedBigInteger('performed_by')->nullable(); // user who triggered (admin, system)
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
            $table->index(['reference']);
        });

        // Add delivery_fee to orders so rider earnings can be calculated
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('delivery_fee', 10, 2)->default(1500)->after('total');
            $table->boolean('dealer_credited')->default(false)->after('delivery_fee');
            $table->boolean('rider_credited')->default(false)->after('dealer_credited');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_fee', 'dealer_credited', 'rider_credited']);
        });
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
