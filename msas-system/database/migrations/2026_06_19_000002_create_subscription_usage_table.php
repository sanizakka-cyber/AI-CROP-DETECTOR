<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key'); // e.g. livestock_records, reports_generated, ai_scans
            $table->unsignedInteger('count')->default(0);
            $table->string('period'); // YYYY-MM, e.g. 2026-06
            $table->timestamps();

            $table->unique(['user_id', 'feature_key', 'period']);
            $table->index(['user_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage');
    }
};
