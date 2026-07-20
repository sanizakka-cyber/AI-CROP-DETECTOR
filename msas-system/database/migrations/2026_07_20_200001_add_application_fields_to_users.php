<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'application_status')) {
                // 'approved' default keeps all existing accounts active
                $table->string('application_status')->default('approved')->after('is_verified');
            }
            if (!Schema::hasColumn('users', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('application_status');
            }
            if (!Schema::hasColumn('users', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('users', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('reviewed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['reviewed_by','reviewed_at','rejection_reason','application_status'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
