<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('consent_given_at')->nullable()->after('onboarding_dismissed_at');
            $table->timestamp('data_export_requested_at')->nullable()->after('consent_given_at');
            $table->timestamp('data_export_completed_at')->nullable()->after('data_export_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['consent_given_at','data_export_requested_at','data_export_completed_at']);
        });
    }
};