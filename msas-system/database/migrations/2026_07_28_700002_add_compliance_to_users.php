<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'consent_given_at')) {
                $table->timestamp('consent_given_at')->nullable()->after('onboarding_dismissed_at');
            }
            if (!Schema::hasColumn('users', 'data_export_requested_at')) {
                $table->timestamp('data_export_requested_at')->nullable()->after('consent_given_at');
            }
            if (!Schema::hasColumn('users', 'data_export_completed_at')) {
                $table->timestamp('data_export_completed_at')->nullable()->after('data_export_requested_at');
            }
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(['consent_given_at','data_export_requested_at','data_export_completed_at'],
                fn($c) => Schema::hasColumn('users', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};