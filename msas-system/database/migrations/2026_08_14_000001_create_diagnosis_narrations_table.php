<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('diagnosis_narrations')) {
            return;
        }

        Schema::create('diagnosis_narrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnosis_id')->constrained()->cascadeOnDelete();
            $table->string('language', 5);
            $table->string('voice', 50)->nullable();
            $table->string('provider', 20)->nullable();
            $table->string('storage_path')->nullable();
            $table->string('audio_format', 10)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            // pending: generation in progress: ready: audio available; failed: generation errored,
            // frontend should fall back to browser TTS rather than retry every request.
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();

            // One canonical audio file per diagnosis+language — regenerating the same
            // language overwrites this row rather than accumulating duplicates.
            $table->unique(['diagnosis_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnosis_narrations');
    }
};
