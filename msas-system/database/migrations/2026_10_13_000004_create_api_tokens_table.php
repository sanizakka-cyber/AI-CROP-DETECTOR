<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per signed-in device. Previously each user had a single
 * users.api_token, so signing in on a second device silently signed the
 * first one out. users.api_token is still written (newest token) so a
 * rollback of this change cannot log anyone out.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_tokens')) {
            return;
        }
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('name', 60)->default('mobile');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
