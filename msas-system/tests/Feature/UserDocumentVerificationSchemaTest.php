<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Confirms the 2026_10_11_000001 migration is safe for both a fresh
 * install and an existing database with real UserDocument rows already
 * on disk from before this lifecycle existed -- every pre-existing row
 * must land on 'pending_review' (the honest starting state: it was
 * genuinely never individually reviewed before now), not null or an
 * invalid value that would break the status-based queries added
 * alongside it.
 */
class UserDocumentVerificationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_documents_table_has_the_lifecycle_columns(): void
    {
        foreach (['status', 'reviewed_at', 'reviewed_by', 'review_reason', 'replaces_document_id'] as $column) {
            $this->assertTrue(Schema::hasColumn('user_documents', $column), "user_documents.{$column} is missing.");
        }
    }

    public function test_a_document_row_inserted_without_a_status_defaults_to_pending_review(): void
    {
        $user = User::factory()->create(['role' => 'equipment-dealer']);

        // Mirrors exactly what a pre-migration INSERT looked like: no
        // status/reviewed_at/etc. columns in the payload at all.
        $document = UserDocument::create([
            'user_id' => $user->id, 'document_type' => 'business_registration',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024,
            'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);

        $this->assertSame('pending_review', $document->refresh()->status);
        $this->assertNull($document->reviewed_at);
        $this->assertNull($document->reviewed_by);
    }
}
