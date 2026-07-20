<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('diagnoses') || Schema::hasColumn('diagnoses', 'image_thumbnail')) {
            return;
        }

        Schema::table('diagnoses', function (Blueprint $table) {
            // Stores a base64-encoded JPEG thumbnail (~30–80 KB) so images survive
            // container restarts on ephemeral hosting (Render, Railway, Fly.io, etc.)
            $table->text('image_thumbnail')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('diagnoses') && Schema::hasColumn('diagnoses', 'image_thumbnail')) {
            Schema::table('diagnoses', function (Blueprint $table) {
                $table->dropColumn('image_thumbnail');
            });
        }
    }
};
