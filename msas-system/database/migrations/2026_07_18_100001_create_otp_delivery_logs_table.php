<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();  // null for password-reset before user lookup
            $table->string('identifier_hint');                  // masked phone/email, never plain
            $table->string('type');                             // registration | password_reset
            $table->string('channel');                          // sms | email
            $table->string('provider')->nullable();             // termii | africas_talking | twilio | log
            $table->boolean('delivered')->default(false);
            $table->string('message_id')->nullable();           // provider's message reference
            $table->text('error')->nullable();                  // provider error when delivered = false
            $table->string('verification_status')->default('pending'); // pending | verified | expired | failed
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'delivered']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_delivery_logs');
    }
};
