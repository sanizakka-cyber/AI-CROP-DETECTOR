<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_pilot')->default(false)->after('is_verified');
            $table->timestamp('onboarding_dismissed_at')->nullable()->after('is_pilot');
            $table->string('rider_status', 20)->nullable()->after('onboarding_dismissed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_pilot', 'onboarding_dismissed_at', 'rider_status']);
        });
    }
};