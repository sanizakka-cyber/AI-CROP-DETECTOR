<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('referrals')) {
            return;
        }

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending|rewarded
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();
            $table->unique(['referrer_id','referred_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('referrals'); }
};