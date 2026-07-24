<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Subscription reminder tracking
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('reminder_7day_sent_at')->nullable()->after('auto_renew');
            $table->timestamp('reminder_1day_sent_at')->nullable()->after('reminder_7day_sent_at');
        });

        // Marketplace order payout tracking
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payout_status', 30)->nullable()->default(null)->after('payment_reference'); // null | requested | approved | paid | rejected
            $table->decimal('payout_amount', 12, 2)->nullable()->after('payout_status');
            $table->timestamp('payout_requested_at')->nullable()->after('payout_amount');
            $table->timestamp('payout_paid_at')->nullable()->after('payout_requested_at');
            $table->string('payout_reference', 100)->nullable()->after('payout_paid_at');
            $table->text('payout_notes')->nullable()->after('payout_reference');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['reminder_7day_sent_at', 'reminder_1day_sent_at']);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payout_status', 'payout_amount', 'payout_requested_at', 'payout_paid_at', 'payout_reference', 'payout_notes']);
        });
    }
};
