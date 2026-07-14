<?php
// ─── MSAS Database Migrations (Run via: php artisan migrate) ───────────────
// This file documents all migrations. Each section = one migration file.

/*
|--------------------------------------------------------------------------
| MIGRATION 1: Add role & profile fields to users table
| File: database/migrations/xxxx_add_role_to_users_table.php
|--------------------------------------------------------------------------
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('farmer')->after('email');
            // roles: ceo, admin, hr, finance, vet, agronomist, farmer, agro-dealer
            $table->string('language')->default('en')->after('role');
            $table->string('state')->default('Katsina')->after('language');
            $table->string('lga')->nullable()->after('state');
            $table->string('village')->nullable()->after('lga');
            $table->string('profile_photo')->nullable()->after('village');
            $table->boolean('is_verified')->default(false)->after('profile_photo');
            $table->boolean('is_active')->default(true)->after('is_verified');
            $table->timestamp('last_seen')->nullable()->after('is_active');
            // Expert fields
            $table->string('license_number')->nullable();
            $table->string('specialization')->nullable();
            $table->integer('years_experience')->nullable();
            $table->decimal('consultation_fee', 12, 2)->nullable();
            $table->string('organization')->nullable();
            // Farmer fields
            $table->decimal('farm_size', 8, 2)->nullable();
            $table->json('crops_grown')->nullable();
            $table->json('livestock_counts')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role','language','state','lga','village',
                'profile_photo','is_verified','is_active','last_seen',
                'license_number','specialization','years_experience',
                'consultation_fee','organization','farm_size','crops_grown','livestock_counts']);
        });
    }
};
