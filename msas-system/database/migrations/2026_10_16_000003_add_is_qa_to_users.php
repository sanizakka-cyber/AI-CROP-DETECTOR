<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Marks dedicated QA test accounts (config/qa.php). Has no effect in production.
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_qa')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_qa')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_qa')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_qa');
            });
        }
    }
};
