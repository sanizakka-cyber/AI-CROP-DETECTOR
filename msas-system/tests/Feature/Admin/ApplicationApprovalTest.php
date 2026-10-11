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

    public function test_admin_can_approve_equipment_dealer_once_the_required_document_is_present(): void
    {
        Mail::fake();
        $dealer = User::factory()->create([
            'role' => 'equipment-dealer', 'application_status' => 'pending', 'is_active' => false,
            'email' => 'real-dealer@example.com',
        ]);
        UserDocument::create([
            'user_id' => $dealer->id, 'document_type' => 'business_registration',
            'document_label' => 'Business Registration Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024,
            'content_base64' => base64_encode('%PDF-1.4 fake certificate contents'),
        ]);

        $response = $this->actingAs($this->admin())
            ->post(route('admin.applications.approve', $dealer));

        $response->assertRedirect(route('admin.applications.index'));
        $this->assertSame('approved', $dealer->refresh()->application_status);
        $this->assertTrue((bool) $dealer->is_active);
        Mail::assertSent(ApplicationApprovedMail::class);
    }

    public function test_admin_can_approve_a_cooperative_only_once_every_required_document_is_present(): void
    {
        $coop = User::factory()->create([
            'role' => 'cooperative', 'application_status' => 'pending', 'is_active' => false,
        ]);
        // Only one of the two documents cooperatives must submit.
        UserDocument::create([
            'user_id' => $coop->id, 'document_type' => 'cooperative_certificate',
            'document_label' => 'Cooperative Certificate', 'original_name' => 'cert.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024,
            'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);

        $blocked = $this->actingAs($this->admin())->post(route('admin.applications.approve', $coop));
        $blocked->assertRedirect(route('admin.applications.show', $coop));
        $this->assertStringContainsString('Members List', session('error'));
        $this->assertSame('pending', $coop->refresh()->application_status);

        UserDocument::create([
            'user_id' => $coop->id, 'document_type' => 'members_list',
            'document_label' => 'Members List (min. 5)', 'original_name' => 'members.pdf',
            'mime_type' => 'application/pdf', 'file_size' => 1024,
            'content_base64' => base64_encode('%PDF-1.4 fake'),
        ]);

        $approved = $this->actingAs($this->admin())->post(route('admin.applications.approve', $coop));
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
}
