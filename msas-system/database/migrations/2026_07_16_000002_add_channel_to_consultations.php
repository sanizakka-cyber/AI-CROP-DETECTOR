<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('consultations')) return;
        Schema::table('consultations', function (Blueprint $table) {
            if (!Schema::hasColumn('consultations', 'channel')) {
                $table->string('channel')->default('in_app')->after('consultation_type');
            }
            if (!Schema::hasColumn('consultations', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['channel', 'payment_reference']);
        });
    }
};
