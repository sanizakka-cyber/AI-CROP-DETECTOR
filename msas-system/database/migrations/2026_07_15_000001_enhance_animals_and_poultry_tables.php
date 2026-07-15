<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── animals table ────────────────────────────────────────────────
        Schema::table('animals', function (Blueprint $table) {
            if (!Schema::hasColumn('animals', 'name')) {
                $table->string('name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('animals', 'species_other')) {
                $table->string('species_other')->nullable()->after('species');
            }
            if (!Schema::hasColumn('animals', 'breed_other')) {
                $table->string('breed_other')->nullable()->after('breed');
            }
            if (!Schema::hasColumn('animals', 'needs_admin_review')) {
                $table->boolean('needs_admin_review')->default(false)->after('notes');
            }
        });

        // Add unique index separately (idempotent — skip if already exists)
        $indexExists = collect(Schema::getIndexes('animals'))
            ->pluck('name')
            ->contains('animals_tag_number_unique');
        if (!$indexExists) {
            Schema::table('animals', function (Blueprint $table) {
                $table->unique('tag_number', 'animals_tag_number_unique');
            });
        }

        // ── poultry_records table ─────────────────────────────────────────
        Schema::table('poultry_records', function (Blueprint $table) {
            if (!Schema::hasColumn('poultry_records', 'bird_type_other')) {
                $table->string('bird_type_other')->nullable()->after('bird_type');
            }
            if (!Schema::hasColumn('poultry_records', 'breed')) {
                $table->string('breed')->nullable()->after('bird_type');
            }
            if (!Schema::hasColumn('poultry_records', 'purpose')) {
                $table->string('purpose')->nullable()->after('quantity');
            }
            if (!Schema::hasColumn('poultry_records', 'needs_admin_review')) {
                $table->boolean('needs_admin_review')->default(false)->after('notes');
            }
        });

        $indexExists = collect(Schema::getIndexes('poultry_records'))
            ->pluck('name')
            ->contains('poultry_records_batch_number_unique');
        if (!$indexExists) {
            Schema::table('poultry_records', function (Blueprint $table) {
                $table->unique('batch_number', 'poultry_records_batch_number_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropUnique('animals_tag_number_unique');
            foreach (['name', 'species_other', 'breed_other', 'needs_admin_review'] as $col) {
                if (Schema::hasColumn('animals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('poultry_records', function (Blueprint $table) {
            $table->dropUnique('poultry_records_batch_number_unique');
            foreach (['bird_type_other', 'breed', 'purpose', 'needs_admin_review'] as $col) {
                if (Schema::hasColumn('poultry_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
