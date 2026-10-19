<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every scan that could not complete, with its reference (SCN-XXXXX) so a
 * farmer can quote it and support can find it (/admin/scan-errors).
 * `detail` is stored with secrets already masked (App\Support\SecretMasker).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('scan_errors')) {
            return;
        }
        Schema::create('scan_errors', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->string('code', 40)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_role', 40)->nullable();
            $table->string('channel', 10);                 // web | api
            $table->string('scan_type', 20)->nullable();
            $table->string('service', 20);                 // website | ai_engine | claude_api
            $table->unsignedSmallInteger('http_status');   // returned to the client
            $table->unsignedSmallInteger('upstream_status')->nullable(); // what the engine / provider answered
            $table->text('detail')->nullable();            // masked
            $table->json('context')->nullable();           // route, field, locale, app version (no secrets)
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_errors');
    }
};
