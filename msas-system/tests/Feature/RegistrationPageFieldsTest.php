<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The registration page's visual redesign must not change what the form
 * collects or says: every field name and id, every placeholder (including
 * the empty ones), the labels and the age/terms ticks stay exactly the same.
 */
class RegistrationPageFieldsTest extends TestCase
{
    private function page(): string
    {
        return $this->get('/register')->assertOk()->getContent();
    }

    public function test_every_submitted_field_name_is_present(): void
    {
        $html = $this->page();

        foreach ([
            'role', 'first_name', 'middle_name', 'last_name', 'identifier',
            'country', 'state', 'lga', 'ward', 'age', 'age_confirmed', 'plan',
            'password', 'password_confirmation', 'terms_accepted',
            '_country_ts', '_state_ts', '_lga_ts',
            'documents[vet_license]', 'documents[accreditation]',
            'documents[professional_license]', 'documents[proof_of_qualification]',
            'documents[cac_registration]', 'documents[business_registration]',
            'documents[company_registration]', 'documents[cooperative_certificate]',
            'documents[members_list]', 'documents[official_documents]',
            'documents[registration_cert]', 'documents[tax_exemption]',
            'documents[institutional_affiliation]', 'documents[research_proposal]',
            'documents[transport_license]', 'documents[id_document]',
            'documents[investment_profile]',
        ] as $name) {
            $this->assertStringContainsString('name="' . $name . '"', $html, "missing field name {$name}");
        }
    }

    public function test_every_field_id_is_present(): void
    {
        $html = $this->page();

        foreach ([
            's2_first_name', 's2_last_name', 's2_middle_name', 's2_identifier',
            's2_age', 's2_age_confirmed', 'ts-country', 'ts-state', 'ts-lga', 's2_ward',
            'h_role', 'h_first_name', 'h_middle_name', 'h_last_name', 'h_identifier',
            'h_country', 'h_state', 'h_lga', 'h_ward', 'h_age', 'h_age_confirmed',
            'password', 'password_confirmation', 'terms', 'reg-form',
            's2-error', 's2-id-icon', 's2-id-badge', 'pw-requirements', 'pw-match-msg',
        ] as $id) {
            $this->assertStringContainsString('id="' . $id . '"', $html, "missing id {$id}");
        }
    }

    public function test_placeholders_are_unchanged(): void
    {
        $html = $this->page();

        $placeholders = [
            's2_first_name'         => '',
            's2_last_name'          => '',
            's2_identifier'         => 'Enter your email address or phone number',
            's2_age'                => 'e.g. 35',
            's2_ward'               => 'Enter ward or village name',
            'password'              => 'Create a password',
            'password_confirmation' => 'Re-enter your password',
        ];
        foreach ($placeholders as $id => $text) {
            $tag = $this->inputTag($html, $id);
            $this->assertStringContainsString('placeholder="' . $text . '"', $tag, "placeholder changed on #{$id}");
        }

        // Middle name has no placeholder at all.
        $this->assertStringNotContainsString('placeholder=', $this->inputTag($html, 's2_middle_name'));

        // Tom Select placeholders are set in the page script.
        foreach (["'Select country…'", "'Select state / province…'", "'Select LGA…'"] as $p) {
            $this->assertStringContainsString("placeholder: {$p}", $html);
        }
    }

    public function test_labels_and_ticks_are_unchanged(): void
    {
        $html = $this->page();

        foreach ([
            'First Name', 'Last Name', 'Middle Name', '(optional)', 'Email Address or Phone Number *',
            'Age', 'I confirm I am 18 years or older.', 'Your Location', 'Country *',
            'State / Province *', 'Local Government Area', 'Ward / Village',
            'Password *', 'Confirm Password *', 'I agree to the', 'Terms of Service', 'Privacy Policy',
            'e.g. name@email.com or 08012345678', 'Select a country first', 'Select a state first',
            'Your Details', 'Create Password', 'Registration Summary', 'I am joining as…',
        ] as $text) {
            $this->assertStringContainsString(e($text), $html, "missing text: {$text}");
        }

        $this->assertMatchesRegularExpression('/<input type="checkbox" id="s2_age_confirmed"/', $html);
        $this->assertMatchesRegularExpression('/<input type="checkbox" id="terms" name="terms_accepted" value="1" required/', $html);
    }

    private function inputTag(string $html, string $id): string
    {
        $this->assertSame(1, preg_match('/<input\b[^>]*\bid="' . preg_quote($id, '/') . '"[^>]*>/s', $html, $m), "input #{$id} not found");

        return $m[0];
    }
}
