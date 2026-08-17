<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// diagnoses(user_id, created_at) already exists — added in
// 2026_07_28_900001_add_performance_indexes.php. users.state is indexed
// there too, but users.lga was missed; the CEO AI Analytics State -> LGA
// drill-down filters on it directly, so it needs its own index.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'lga')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->hasIndex('users', 'users_lga_index')) {
                    $table->index('lga', 'users_lga_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                try { $table->dropIndex('users_lga_index'); } catch (\Throwable) {}
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        try {
            $indexes = \Illuminate\Support\Facades\DB::select(
                "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                [$table, $indexName]
            );
            return !empty($indexes);
        } catch (\Throwable) {
            return false;
        }
    }
};
