<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'two_factor_enabled')) {
                $table->boolean('two_factor_enabled')->default(false)->after('password');
            }
            if (!Schema::hasColumn('users', 'two_factor_code')) {
                $table->string('two_factor_code', 6)->nullable()->after('two_factor_enabled');
            }
            if (!Schema::hasColumn('users', 'two_factor_expires_at')) {
                $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code');
            }
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(['two_factor_enabled','two_factor_code','two_factor_expires_at'],
                fn($c) => Schema::hasColumn('users', $c));
            if ($cols) $table->dropColumn(array_values($cols));
        });
    }
};