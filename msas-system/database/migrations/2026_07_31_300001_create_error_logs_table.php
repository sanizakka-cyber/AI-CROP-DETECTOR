<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('category', 30)->default('app')->index();
            $table->string('level', 20)->default('error');
            $table->string('exception_class', 255)->nullable();
            $table->text('message');
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('method', 10)->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_role', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('resolved')->default(false)->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
