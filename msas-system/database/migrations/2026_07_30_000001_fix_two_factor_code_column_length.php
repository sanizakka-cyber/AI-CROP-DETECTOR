<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // two_factor_code was mistakenly defined as string(6) — a VARCHAR(6) column
            // that is far too short to store a bcrypt hash (60 chars). PostgreSQL rejects
            // any save() call with a PDOException; this column must be at least 60 chars.
            $table->string('two_factor_code', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullify any stored hashes before shrinking — they would not fit anyway
            \Illuminate\Support\Facades\DB::table('users')->update(['two_factor_code' => null]);
            $table->string('two_factor_code', 6)->nullable()->change();
        });
    }
};
