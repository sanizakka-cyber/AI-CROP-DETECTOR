<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| MSAS Granular RBAC — Permissions Tables
| Run order: after create_core_tables.php and add_role_to_users.php
|
| Usage in routes:
|   Route::get('/admin', ...)->middleware('permission:admin:view_dashboard');
|
| Register PermissionMiddleware in bootstrap/app.php → withMiddleware:
|   $middleware->alias([
|       ...
|       'permission' => \App\Http\Middleware\PermissionMiddleware::class,
|   ]);
|--------------------------------------------------------------------------
*/

return new class extends Migration {
    public function up(): void
    {
        // ── Permissions catalogue ───────────────────────────────────────────────
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();       // e.g. 'diagnosis:create'
            $table->string('category');             // user | farm | diagnosis | ...
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // ── Role → Permission mapping ───────────────────────────────────────────
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role');                 // farmer | vet | admin | ceo | ...
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->unique(['role', 'permission_id']);
            $table->timestamps();
        });

        // ── Flag QA / test accounts so they are easy to remove pre-production ──
        if (!Schema::hasColumn('users', 'is_test_account')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_test_account')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_test_account')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_test_account');
            });
        }
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
