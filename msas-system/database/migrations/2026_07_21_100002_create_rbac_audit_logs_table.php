<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rbac_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80); // role_created, role_updated, permission_changed, role_assigned, role_removed, staff_created, staff_suspended, password_reset
            $table->string('target_type', 40); // StaffRole | User
            $table->unsignedBigInteger('target_id');
            $table->string('target_label')->nullable(); // human-readable name for display
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['actor_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rbac_audit_logs');
    }
};
