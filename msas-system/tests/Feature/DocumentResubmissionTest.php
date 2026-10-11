<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The signed-link resubmission flow (Phase 2 of the document-verification
 * audit): an applicant can't log in while pending/rejected (that block is
 * intentional and untouched -- see AuthenticatedSessionController), so
 * replacing a rejected document happens entirely through a temporary
 * signed URL emailed by Admin\ApplicationController::rejectDocument(),
 * with no session and no broader access than this one document.
 */
class DocumentResubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function signedUrlFor(UserDocument $document): string
    {
        return URL::temporarySignedRoute('documents.resubmit', now()->addDays(7), ['document' => $document->id]);
    }

    public function test_an_unsigned_url_to_the_resubmission_route_is_rejected(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration', 'status' => 'rejected',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);

        $response = $this->get('/documents/' . $document->id . '/resubmit');

        $response->assertStatus(403);
    }

    public function test_the_signed_link_shows_the_rejection_reason_and_accepts_a_replacement(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration', 'status' => 'rejected',
            'review_reason' => 'The scan is too blurry to read.',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);
        $url = $this->signedUrlFor($document);

        $this->get($url)->assertOk()->assertSee('The scan is too blurry to read.');

        $file = UploadedFile::fake()->image('new-cert.jpg', 300, 300);
        $response = $this->post($url, ['file' => $file]);

        $response->assertOk();
        $replacement = UserDocument::where('replaces_document_id', $document->id)->first();
        $this->assertNotNull($replacement, 'A replacement document must be created.');
        $this->assertSame('pending_review', $replacement->status);
        $this->assertSame($document->user_id, $replacement->user_id);
        $this->assertSame($document->document_type, $replacement->document_type);

        // The original, rejected row must survive untouched -- history, not mutation.
        $this->assertSame('rejected', $document->refresh()->status);
        $this->assertSame('The scan is too blurry to read.', $document->review_reason);
    }

    public function test_the_same_signed_link_cannot_be_used_twice(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration', 'status' => 'rejected',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);
        $url = $this->signedUrlFor($document);

        $this->post($url, ['file' => UploadedFile::fake()->image('first.jpg', 300, 300)])->assertOk();
        $this->assertSame(1, UserDocument::where('replaces_document_id', $document->id)->count());

        $second = $this->post($url, ['file' => UploadedFile::fake()->image('second.jpg', 300, 300)]);

        $second->assertOk();
        $second->assertSee('Already Used');
        $this->assertSame(1, UserDocument::where('replaces_document_id', $document->id)->count(), 'A second use of the same link must not create a second replacement.');
    }

    public function test_the_link_refuses_a_document_that_is_no_longer_awaiting_resubmission(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration', 'status' => 'approved',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);
        $url = $this->signedUrlFor($document);

        $response = $this->post($url, ['file' => UploadedFile::fake()->image('new.jpg', 300, 300)]);

        $response->assertOk();
        $response->assertSee('Already Used');
        $this->assertSame(0, UserDocument::where('replaces_document_id', $document->id)->count(), 'An approved document must never be overwritten through this link.');
    }

    public function test_an_unreadable_replacement_file_does_not_create_a_broken_record(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration', 'status' => 'rejected',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);
        $url = $this->signedUrlFor($document);

        // Oversized / wrong-type uploads are rejected by validation before
        // ever reaching the file-read step -- covered here is the empty-file case.
        $response = $this->post($url, ['file' => UploadedFile::fake()->create('empty.jpg', 0, 'image/jpeg')]);

        $response->assertSessionHasErrors('file');
        $this->assertSame(0, UserDocument::where('replaces_document_id', $document->id)->count());
    }
}
