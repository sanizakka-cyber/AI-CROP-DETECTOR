<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invite_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('created_by')->constrained('users');
            $table->string('plan', 50)->default('basic');   // which plan to auto-trial
            $table->unsignedSmallInteger('max_uses')->default(1);
            $table->unsignedSmallInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['code', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invite_codes');
    }
};