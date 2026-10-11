<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document verification lifecycle (Pending Review / Approved / Rejected /
 * Resubmission Required), per document rather than only per application.
 * Previously a UserDocument row had no status of its own at all -- an
 * admin could only approve or reject the whole application, with no way
 * to record a decision on an individual document, no rejection reason,
 * no reviewer, and no way for an applicant to replace a bad upload.
 *
 * replaces_document_id links a resubmitted upload back to the document it
 * replaces, preserving the old row (and its review history) untouched --
 * resubmission always inserts a new row rather than mutating the old one.
 *
 * All columns are nullable/defaulted, so this is purely additive and safe
 * on both a fresh install and an existing database with real rows: every
 * existing UserDocument becomes 'pending_review' (the honest starting
 * state -- these were never actually reviewed individually before now).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('user_documents', 'status')) {
                $table->string('status', 30)->default('pending_review')->after('content_base64');
            }
            if (!Schema::hasColumn('user_documents', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('user_documents', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('user_documents', 'review_reason')) {
                $table->text('review_reason')->nullable()->after('reviewed_by');
            }
            if (!Schema::hasColumn('user_documents', 'replaces_document_id')) {
                $table->foreignId('replaces_document_id')->nullable()->after('review_reason')
                    ->constrained('user_documents')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            foreach (['replaces_document_id', 'review_reason', 'reviewed_by', 'reviewed_at', 'status'] as $column) {
                if (Schema::hasColumn('user_documents', $column)) {
                    if (in_array($column, ['reviewed_by', 'replaces_document_id'], true)) {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
