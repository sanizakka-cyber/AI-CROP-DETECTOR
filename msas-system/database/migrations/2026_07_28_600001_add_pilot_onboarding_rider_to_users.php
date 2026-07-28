<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_pilot')) {
                $table->boolean('is_pilot')->default(false)->after('is_verified');
            }
            if (!Schema::hasColumn('users', 'onboarding_dismissed_at')) {
                $table->timestamp('onboarding_dismissed_at')->nullable()->after('is_pilot');
            }
            if (!Schema::hasColumn('users', 'rider_status')) {
                $table->string('rider_status', 20)->nullable()->after('onboarding_dismissed_at');
            }
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(['is_pilot','onboarding_dismissed_at','rider_status'],
                fn($c) => Schema::hasColumn('users', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};