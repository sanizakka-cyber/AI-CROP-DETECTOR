<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('egg_productions')) return;
        Schema::table('egg_productions', function (Blueprint $table) {
            if (!Schema::hasColumn('egg_productions', 'poultry_record_id')) {
                $table->foreignId('poultry_record_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('poultry_records')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('egg_productions', function (Blueprint $table) {
            if (Schema::hasColumn('egg_productions', 'poultry_record_id')) {
                $table->dropForeign(['poultry_record_id']);
                $table->dropColumn('poultry_record_id');
            }
        });
    }
};
