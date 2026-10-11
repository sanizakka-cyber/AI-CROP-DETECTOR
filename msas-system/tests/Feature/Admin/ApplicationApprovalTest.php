<?php

namespace Tests\Feature\Admin;

use App\Mail\ApplicationApprovedMail;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Regression coverage for the registration/document-verification audit:
 * previously nothing on the backend checked that a regulated role (e.g.
 * equipment-dealer) had actually uploaded its required documents before
 * Admin\ApplicationController::approve() could activate the account --
 * only the registration form's own upload field was 'nullable'. These
 * tests exercise the real routes end to end, not just the helper methods.
 */
class ApplicationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'application_status' => 'approved', 'is_active' => true]);
    }

    public function test_farmer_registers_without_any_document_prompt_and_is_approved_immediately(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Amina',
            'last_name'  => 'Bello',
            'identifier' => '+2348011112222',
            'role'       => 'farmer',
            'password'   => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::where('phone', '+2348011112222')->first();
        $this->assertNotNull($user);
        $this->assertSame('approved', $user->application_status, 'Farmers must not be routed into the document-approval queue.');
        $this->assertTrue((bool) $user->is_active);
        $this->assertSame(0, $user->documents()->count());
        $response->assertSessionDoesntHaveErrors();
    }

    public function test_equipment_dealer_registration_requires_approval_and_defaults_to_inactive(): void
    {
        $this->post('/register', [
            'first_name' => 'Chuka',
            'last_name'  => 'Obi',
            'identifier' => 'dealer-no-docs@example.com',
            'role'       => 'equipment-dealer',
            'password'   => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $user = User::where('email', 'dealer-no-docs@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('pending', $user->application_status);
        $this->assertFalse((bool) $user->is_active);
    }

    public function test_admin_cannot_approve_equipment_dealer_with_no_uploaded_documents(): void
    {
        $dealer = User::factory()->create([
            'role' => 'equipment-dealer', 'application_status' => 'pending', 'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.applications.approve', $dealer));

        $response->assertRedirect(route('admin.applications.show', $dealer));
        $this->assertStringContainsString('Business Registration Certificate', session('error'));
        $this->assertSame('pending', $dealer->refresh()->application_status, 'Approval must be refused at the backend, not just hidden from the UI.');
        $this->assertFalse((bool) $dealer->is_active);
    }

    public function test_admin_cannot_approve_equipment_dealer_whose_document_is_uploaded_but_not_yet_reviewed(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer', 'application_status' => 'pending', 'is_active' => false]);
        UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024,
            'content_base64' => base64_encode('%PDF-1.4 fake'),
            // status defaults to pending_review -- uploaded is not the same as verified.
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.applications.approve', $dealer));

        $response->assertRedirect(route('admin.applications.show', $dealer));
        $this->assertStringContainsString('Business Registration Certificate', session('error'));
        $this->assertSame('pending', $dealer->refresh()->application_status, 'Uploading a document is not the same as it being verified.');
    }

    public function test_admin_can_approve_equipment_dealer_once_the_required_document_is_individually_approved(): void
    {
        Mail::fake();
        $dealer = User::factory()->create([
            'role' => 'equipment-dealer', 'application_status' => 'pending', 'is_active' => false,
            'email' => 'real-dealer@example.com',
        ]);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024,
            'content_base64' => base64_encode('%PDF-1.4 fake certificate contents'),
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.applications.documents.approve', $document));
        $this->assertSame('approved', $document->refresh()->status);
        $this->assertSame($admin->id, $document->reviewed_by);
        $this->assertNotNull($document->reviewed_at);

        $response = $this->actingAs($admin)->post(route('admin.applications.approve', $dealer));

        $response->assertRedirect(route('admin.applications.index'));
        $this->assertSame('approved', $dealer->refresh()->application_status);
        $this->assertTrue((bool) $dealer->is_active);
        Mail::assertSent(ApplicationApprovedMail::class);
    }

    public function test_admin_can_approve_a_cooperative_only_once_every_required_document_is_individually_approved(): void
    {
        $coop = User::factory()->create(['role' => 'cooperative', 'application_status' => 'pending', 'is_active' => false]);
        $admin = $this->admin();

        $cert = UserDocument::create([
            'user_id' => $coop->id, 'document_type' => 'cooperative_certificate', 'status' => 'approved',
            'document_label' => 'Cooperative Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);

        // Only one of the two required documents is approved so far.
        $blocked = $this->actingAs($admin)->post(route('admin.applications.approve', $coop));
        $blocked->assertRedirect(route('admin.applications.show', $coop));
        $this->assertStringContainsString('Members List', session('error'));
        $this->assertSame('pending', $coop->refresh()->application_status);

        $members = UserDocument::create([
            'user_id' => $coop->id, 'document_type' => 'members_list',
            'document_label' => 'Members List (min. 5)', 'original_name' => 'members.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);
        $this->actingAs($admin)->post(route('admin.applications.documents.approve', $members));

        $approved = $this->actingAs($admin)->post(route('admin.applications.approve', $coop));
        $approved->assertRedirect(route('admin.applications.index'));
        $this->assertSame('approved', $coop->refresh()->application_status);
    }

    public function test_viewing_a_document_with_empty_stored_content_returns_a_clear_error_not_a_blank_success(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 0,
            'content_base64' => '',
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.applications.document', $document));

        $response->assertStatus(404);
        $response->assertSee('not stored correctly', false);
    }

    public function test_viewing_a_correctly_stored_document_serves_its_real_content_and_mime_type(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);
        $content = '%PDF-1.4 genuine certificate bytes';
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => strlen($content),
            'content_base64' => base64_encode($content),
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.applications.document', $document));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($content, $response->getContent());
    }

    public function test_admin_rejecting_a_document_records_reason_reviewer_and_emails_a_resubmission_link(): void
    {
        Mail::fake();
        $dealer = User::factory()->create(['role' => 'equipment-dealer', 'email' => 'dealer@example.com']);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.applications.documents.reject', $document), [
            'reason' => 'The certificate photo is too blurry to read.',
            'new_status' => 'resubmission_required',
        ]);

        $response->assertRedirect();
        $document->refresh();
        $this->assertSame('resubmission_required', $document->status);
        $this->assertSame('The certificate photo is too blurry to read.', $document->review_reason);
        $this->assertSame($admin->id, $document->reviewed_by);
        $this->assertNotNull($document->reviewed_at);
        Mail::assertSent(\App\Mail\DocumentResubmissionRequiredMail::class);
    }

    public function test_an_already_approved_document_cannot_be_rejected_or_re_reviewed(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration', 'status' => 'approved',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.applications.documents.reject', $document), [
            'reason' => 'changed my mind', 'new_status' => 'rejected',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('already reviewed', session('error'));
        $this->assertSame('approved', $document->refresh()->status, 'Approved documents must not be silently overwritten.');
    }

    public function test_a_farmer_cannot_review_or_view_another_users_document(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);
        $document = UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024, 'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);
        $farmer = User::factory()->create(['role' => 'farmer']);

        // RoleMiddleware redirects a farmer to their own dashboard rather than
        // a bare 403 (it has a redirect target for 'farmer') -- either way,
        // the point is the admin-only action never runs.
        $this->actingAs($farmer)->get(route('admin.applications.document', $document))->assertRedirect(route('farmer.dashboard'));
        $this->actingAs($farmer)->post(route('admin.applications.documents.approve', $document))->assertRedirect(route('farmer.dashboard'));
        $this->actingAs($farmer)->post(route('admin.applications.documents.reject', $document), [
            'reason' => 'x', 'new_status' => 'rejected',
        ])->assertRedirect(route('farmer.dashboard'));

        $this->assertSame('pending_review', $document->refresh()->status);
    }

    public function test_admin_can_request_a_never_uploaded_required_document_via_signed_link(): void
    {
        Mail::fake();
        $dealer = User::factory()->create(['role' => 'equipment-dealer', 'email' => 'dealer@example.com']);

        $response = $this->actingAs($this->admin())->post(route('admin.applications.documents.request', $dealer), [
            'document_type' => 'business_registration',
        ]);

        $response->assertRedirect();
        $placeholder = UserDocument::where('user_id', $dealer->id)->where('document_type', 'business_registration')->first();
        $this->assertNotNull($placeholder);
        $this->assertSame('resubmission_required', $placeholder->status);
        $this->assertSame('', $placeholder->content_base64, 'A requested-but-not-yet-uploaded document has no content yet.');
        Mail::assertSent(\App\Mail\DocumentResubmissionRequiredMail::class);

        // The applicant can then use the exact same resubmission link as a real rejection would produce.
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('documents.resubmit', now()->addDays(7), ['document' => $placeholder->id]);
        $upload = $this->post($url, ['file' => \Illuminate\Http\UploadedFile::fake()->image('cert.jpg', 300, 300)]);
        $upload->assertOk();
        $this->assertSame(1, UserDocument::where('replaces_document_id', $placeholder->id)->count());
    }

    public function test_admin_cannot_request_a_document_type_that_is_not_part_of_this_roles_requirements(): void
    {
        $dealer = User::factory()->create(['role' => 'equipment-dealer']);

        $response = $this->actingAs($this->admin())->post(route('admin.applications.documents.request', $dealer), [
            'document_type' => 'vet_license',
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('not a recognized document requirement', session('error'));
        $this->assertSame(0, UserDocument::where('user_id', $dealer->id)->count());
    }
}
