<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two ways to record where a sample was collected: GPS capture or manual
 * entry. Adds how the location was entered (location_source: gps, manual,
 * gps_edited), when the GPS fix was taken, how State/LGA were filled
 * (place_names_source: the user, or the server's boundary lookup, which is
 * also how an offline GPS capture gets its names on sync), and the scan
 * place's GPS, kept apart from the collection location when the farmer
 * says they are scanning somewhere else. All nullable.
 */
return new class extends Migration
{
    private const COLUMNS = ['location_source', 'gps_captured_at', 'place_names_source', 'scan_latitude', 'scan_longitude', 'scan_accuracy_meters'];

    public function up(): void
    {
        Schema::table('collection_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('collection_locations', 'location_source')) {
                $table->string('location_source', 16)->nullable();
            }
            if (!Schema::hasColumn('collection_locations', 'gps_captured_at')) {
                $table->timestamp('gps_captured_at')->nullable();
            }
            if (!Schema::hasColumn('collection_locations', 'place_names_source')) {
                $table->string('place_names_source', 64)->nullable();
            }
            if (!Schema::hasColumn('collection_locations', 'scan_latitude')) {
                $table->decimal('scan_latitude', 10, 7)->nullable();
                $table->decimal('scan_longitude', 10, 7)->nullable();
                $table->decimal('scan_accuracy_meters', 8, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $col) {
            if (Schema::hasColumn('collection_locations', $col)) {
                Schema::table('collection_locations', fn (Blueprint $table) => $table->dropColumn($col));
            }
        }
    }
};
