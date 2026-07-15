<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');
            $table->string('species_other')->nullable()->after('species');
            $table->string('breed_other')->nullable()->after('breed');
            $table->boolean('needs_admin_review')->default(false)->after('notes');
            // Uniqueness constraint prevents tag collisions
            $table->unique('tag_number')->change();
        });

        Schema::table('poultry_records', function (Blueprint $table) {
            $table->string('breed')->nullable()->after('bird_type');
            $table->string('bird_type_other')->nullable()->after('bird_type');
            $table->string('purpose')->nullable()->after('quantity'); // meat/egg/breeding/dual-purpose
            $table->boolean('needs_admin_review')->default(false)->after('notes');
            $table->unique('batch_number')->change();
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropUnique(['tag_number']);
            $table->dropColumn(['name', 'species_other', 'breed_other', 'needs_admin_review']);
        });

        Schema::table('poultry_records', function (Blueprint $table) {
            $table->dropUnique(['batch_number']);
            $table->dropColumn(['breed', 'bird_type_other', 'purpose', 'needs_admin_review']);
        });
    }
};
