<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            // Channel: how the farmer wants to be consulted
            $table->string('channel')->default('in_app')->after('consultation_type');
            // Paystack reference for consultation payment
            $table->string('payment_reference')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['channel', 'payment_reference']);
        });
    }
};
