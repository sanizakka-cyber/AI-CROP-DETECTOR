<?php

namespace Tests\Unit;

use App\Services\Speech\ScanResultSource;
use App\Services\Speech\TranslationIntegrity;
use Tests\TestCase;

/**
 * "Listen to your result": the source document built from a scan, and the
 * automated checks that a translation kept every number, unit and product name.
 */
class TranslationIntegrityTest extends TestCase
{
    private const SOURCE = [
        'observations'    => ['Brown lesions on the leaf.', 'White mould under the leaf.'],
        'explanation'     => 'Likely condition: Late blight. The AI is 72% sure of this.',
        'recommendations' => ['Spray Mancozeb 80% WP at 2.5 g per 1 L of water every 7-10 days.', 'Remove infected leaves.'],
        'safety_note'     => 'This is AI guidance and may be wrong.',
    ];

    private function hausa(array $over = []): array
    {
        return array_merge([
            'observations'    => ['Raunuka masu ruwan ƙasa a kan ganye.', 'Farin fumfuna a ƙarƙashin ganye.'],
            'explanation'     => 'Cutar da ake zato: Late blight. AI tana da tabbacin 72%.',
            'recommendations' => ['Fesa Mancozeb 80% WP, 2.5 g a cikin 1 L na ruwa kowane kwana 7-10.', 'Cire ganyen da suka kamu.'],
            'safety_note'     => 'Wannan shawarar AI ce kuma tana iya yin kuskure.',
        ], $over);
    }

    public function test_a_faithful_translation_passes(): void
    {
        $this->assertSame([], TranslationIntegrity::problems(self::SOURCE, $this->hausa(), ['Mancozeb', '80% WP']));
    }

    public function test_a_changed_dose_is_caught(): void
    {
        $bad = $this->hausa(['recommendations' => ['Fesa Mancozeb 80% WP, 25 g a cikin 1 L na ruwa kowane kwana 7-10.', 'Cire ganyen da suka kamu.']]);
        $problems = TranslationIntegrity::problems(self::SOURCE, $bad, ['Mancozeb']);
        $this->assertContains('number 2.5 is missing', $problems);
        $this->assertContains('number 25 was added', $problems);
        $this->assertContains('quantity "2.5g" changed', $problems);
    }

    public function test_a_changed_unit_is_caught(): void
    {
        $bad = $this->hausa(['recommendations' => ['Fesa Mancozeb 80% WP, 2.5 kg a cikin 1 L na ruwa kowane kwana 7-10.', 'Cire ganyen da suka kamu.']]);
        $this->assertContains('quantity "2.5g" changed', TranslationIntegrity::problems(self::SOURCE, $bad));
    }

    public function test_a_translated_product_name_is_caught(): void
    {
        $bad = $this->hausa(['recommendations' => ['Fesa maganin manko 80% WP, 2.5 g a cikin 1 L na ruwa kowane kwana 7-10.', 'Cire ganyen da suka kamu.']]);
        $this->assertContains('"Mancozeb" was not kept as written', TranslationIntegrity::problems(self::SOURCE, $bad, ['Mancozeb']));
    }

    public function test_a_dropped_recommendation_or_safety_note_is_caught(): void
    {
        $bad = $this->hausa(['recommendations' => ['Fesa Mancozeb 80% WP, 2.5 g a cikin 1 L na ruwa kowane kwana 7-10.'], 'safety_note' => ' ']);
        $problems = TranslationIntegrity::problems(self::SOURCE, $bad);
        $this->assertContains('recommendations: 1 items, source has 2', $problems);
        $this->assertContains('safety_note is empty', $problems);
    }

    public function test_source_is_built_from_the_scan_only(): void
    {
        $src = ScanResultSource::fromAttributes([
            'assessment_outcome'     => 'assessed',
            'subject_name'           => 'Tomato', 'detected_part' => 'leaf', 'health_status' => 'diseased',
            'symptoms_identified'    => "1. Brown lesions\n2. White mould",
            'severity_level'         => 'Moderate',
            'disease_name'           => 'Late blight (Phytophthora infestans)',
            'scientific_name'        => 'Phytophthora infestans',
            'confidence_score'       => 72.4,
            'explanation'            => 'Cool wet weather favours it',
            'first_aid_steps'        => 'Remove infected leaves; Avoid overhead watering',
            'recommended_medication' => 'Spray Mancozeb 80% WP at 2.5 g per litre',
            'fertilizer_recommendation' => 'None',
            'language'               => 'en',
        ]);

        $this->assertSame([
            'observations'    => ['Subject: Tomato (leaf).', 'Health status: diseased.', 'Brown lesions.', 'White mould.', 'Severity: Moderate.'],
            'explanation'     => 'Likely condition: Late blight (Phytophthora infestans). The AI is 72% sure of this. Cool wet weather favours it.',
            'recommendations' => ['Remove infected leaves.', 'Avoid overhead watering.', 'Spray Mancozeb 80% WP at 2.5 g per litre.'],
            'safety_note'     => config('speech.safety_note_en'),
        ], $src['content']);
        $this->assertSame('en', $src['source_lang']);
        $this->assertContains('Phytophthora infestans', $src['protected_terms']);
        $this->assertContains('Mancozeb', $src['protected_terms']);
        $this->assertContains('80% WP', $src['protected_terms']);
        $this->assertNotContains('Spray', $src['protected_terms']);
    }

    public function test_a_rejected_photo_says_so_and_gives_the_photo_tips(): void
    {
        $src = ScanResultSource::fromAttributes([
            'assessment_outcome' => 'poor_image',
            'disease_name'       => 'Photo too unclear to assess',
            'explanation'        => 'The leaf is blurred',
            'image_issues'       => ['Blurred'],
            'photo_tips'         => ['Hold the phone steady', 'Use daylight'],
            'confidence_score'   => null,
        ]);
        $this->assertSame(['Blurred.'], $src['content']['observations']);
        $this->assertSame('Photo too unclear to assess. The leaf is blurred.', $src['content']['explanation']);
        $this->assertSame(['Hold the phone steady.', 'Use daylight.'], $src['content']['recommendations']);
        $this->assertStringNotContainsString('%', $src['content']['explanation']);
    }
}
