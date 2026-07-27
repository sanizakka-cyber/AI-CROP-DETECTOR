<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Consultation expert accept/decline tracking
        Schema::table('consultations', function (Blueprint $table) {
            $table->string('consultant_status', 30)->nullable()->after('status');
            // null | pending_acceptance | accepted | declined_by_expert
            $table->timestamp('consultant_accepted_at')->nullable()->after('consultant_status');
            $table->text('consultant_decline_reason')->nullable()->after('consultant_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['consultant_status', 'consultant_accepted_at', 'consultant_decline_reason']);
        });
    }
};
