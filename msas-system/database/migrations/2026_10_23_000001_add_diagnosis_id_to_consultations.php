<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Request Vet / Ask an expert" on a scan result opens the consultation form
 * with that scan attached. The consultation keeps which scan it was about,
 * so the vet or agronomist sees the photo and the AI's result. Nullable:
 * most consultations are not about a scan; deleting a scan keeps the request.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('consultations', 'diagnosis_id')) {
            Schema::table('consultations', function (Blueprint $table) {
                $table->foreignId('diagnosis_id')->nullable()->constrained('diagnoses')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('consultations', 'diagnosis_id')) {
            Schema::table('consultations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('diagnosis_id');
            });
        }
    }
};
