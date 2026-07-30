<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // users.reviewed_by was added in 2026_07_20_200001 as unsignedBigInteger with no FK.
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'reviewed_by')
            && !Schema::hasIndex('users', 'users_reviewed_by_foreign')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // orders.assigned_by was added in 2026_07_24_200001 as unsignedBigInteger with no FK.
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'assigned_by')
            && !Schema::hasIndex('orders', 'orders_assigned_by_foreign')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // wallet_transactions.performed_by was added in 2026_07_27_200001 as unsignedBigInteger with no FK.
        if (Schema::hasTable('wallet_transactions') && Schema::hasColumn('wallet_transactions', 'performed_by')
            && !Schema::hasIndex('wallet_transactions', 'wallet_transactions_performed_by_foreign')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasIndex('users', 'users_reviewed_by_foreign')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['reviewed_by']);
            });
        }

        if (Schema::hasTable('orders') && Schema::hasIndex('orders', 'orders_assigned_by_foreign')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['assigned_by']);
            });
        }

        if (Schema::hasTable('wallet_transactions') && Schema::hasIndex('wallet_transactions', 'wallet_transactions_performed_by_foreign')) {
            Schema::table('wallet_transactions', function (Blueprint $table) {
                $table->dropForeign(['performed_by']);
            });
        }
    }
};
