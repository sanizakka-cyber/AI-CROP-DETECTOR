<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'referral_code')) {
                $table->string('referral_code', 20)->nullable()->unique()->after('data_export_completed_at');
            }
            if (!Schema::hasColumn('users', 'referred_by')) {
                $table->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            }
            if (!Schema::hasColumn('users', 'nps_score')) {
                $table->tinyInteger('nps_score')->nullable()->after('referred_by');
            }
            if (!Schema::hasColumn('users', 'nps_rated_at')) {
                $table->timestamp('nps_rated_at')->nullable()->after('nps_score');
            }
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'referred_by')) {
                $table->dropForeign(['referred_by']);
            }
            $cols = array_filter(['referral_code','referred_by','nps_score','nps_rated_at'],
                fn($c) => Schema::hasColumn('users', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};