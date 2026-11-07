<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Where the profile's State, LGA and Ward/Village came from: 'gps' when the
 * user kept what their location filled in, 'user' when they chose or typed
 * it (same meaning as collection_locations.field_sources). Additive only.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'location_field_sources')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('location_field_sources')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'location_field_sources')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('location_field_sources');
            });
        }
    }
};
