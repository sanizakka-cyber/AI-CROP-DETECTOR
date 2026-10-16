<?php

namespace Tests\Unit;

use App\Services\Speech\Text\EnglishNumbers;
use App\Services\Speech\Text\HausaNumbers;
use App\Services\Speech\Text\IgboNumbers;
use App\Services\Speech\Text\NumberSpeller;
use App\Services\Speech\Text\SpeechPreprocessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * "Listen to your result": text preparation for the voice.
 * Hausa/Igbo number words follow the standard decimal forms; they are part of
 * the native-speaker review before those languages go live.
 */
class SpeechPreprocessorTest extends TestCase
{
    public static function numbers(): array
    {
        return [
            [0, 'zero', 'sifili', 'efu'],
            [15, 'fifteen', 'goma sha biyar', 'iri na ise'],
            [21, 'twenty-one', 'ashirin da ɗaya', 'iri abụọ na otu'],
            [125, 'one hundred and twenty-five', 'ɗari da ashirin da biyar', 'otu narị na iri abụọ na ise'],
            [250, 'two hundred and fifty', 'ɗari biyu da hamsin', 'narị abụọ na iri ise'],
            [2500, 'two thousand five hundred', 'dubu biyu da ɗari biyar', 'puku abụọ na narị ise'],
        ];
    }

    #[DataProvider('numbers')]
    public function test_numbers_are_spelled_per_language(int $n, string $en, string $ha, string $ig): void
    {
        $this->assertSame($en, (new EnglishNumbers())->integer($n));
        $this->assertSame($ha, (new HausaNumbers())->integer($n));
        $this->assertSame($ig, (new IgboNumbers())->integer($n));
    }

    public function test_languages_without_a_reviewed_speller_keep_digits(): void
    {
        $this->assertNull(NumberSpeller::for('yo'));
        $this->assertNull((new HausaNumbers())->decimal('2', '5'));
        $p = new SpeechPreprocessor();
        $this->assertSame('Ẹ fọ́n 15 ml sínú 2 L omi.', $p->spoken('Ẹ fọ́n 15 ml sínú 2 L omi.', 'yo'));
    }

    public function test_english_units_ranges_percent_dates_acronyms_and_lexicon(): void
    {
        $p = new SpeechPreprocessor();
        $out = $p->spoken($p->clean('Spray **Mancozeb 80% WP** at 2.5 g per 1 L 🌱, every 7-10 days. Check your GPS and LGA on MSAS FarmAI by 15/10/2026.'), 'en');
        $this->assertSame(
            'Spray Mancozeb eighty percent double-you pee at two point five grams per one litre, every seven to ten days. '
            . 'Check your jee pee ess and el jee ay on em es ey es fam ey ay by fifteen October two thousand and twenty-six.',
            $out
        );
    }

    public function test_hausa_puts_the_unit_before_the_number(): void
    {
        $p = new SpeechPreprocessor();
        $this->assertSame('Ɗauki kilogiram ashirin da biyar na taki, kashi biyar cikin ɗari.', $p->spoken('Ɗauki 25 kg na taki, 5%.', 'ha'));
        $this->assertSame('mililita iri na ise n\'ime lita abụọ', $p->spoken('15 ml n\'ime 2 L', 'ig'));
    }

    public function test_admin_lexicon_and_unit_words_override_defaults(): void
    {
        $p = new SpeechPreprocessor();
        $this->assertSame('kilo biyar', $p->spoken('5 kg', 'ha', [], ['kg' => 'kilo']));
        $this->assertSame('fam ay', $p->spoken('FarmAI', 'ha', ['FarmAI' => 'fam ay']));
    }

    public function test_special_letters_and_tone_marks_are_never_stripped(): void
    {
        $p = new SpeechPreprocessor();
        foreach (['Hausa: ɓ ɗ ƙ ƴ Ɓ Ɗ Ƙ Ƴ', 'Yorùbá: ẹ ọ ṣ Ẹ̀ ọ́ ọ̀rọ̀', 'Igbo: ị ọ ụ ṅ Ị Ọ Ụ Ṅ'] as $text) {
            $this->assertSame($text, $p->clean($text));
            $this->assertSame($text, $p->spoken($text, 'xx'));
        }
    }

    public function test_decomposed_input_is_normalised_to_nfc(): void
    {
        if (!class_exists(\Normalizer::class)) {
            $this->markTestSkipped('No Normalizer (intl or symfony/polyfill-intl-normalizer).');
        }
        $nfc = 'ọ̀rọ̀ ṣe pàtàkì';
        $nfd = \Normalizer::normalize($nfc, \Normalizer::FORM_D);
        $this->assertNotSame($nfc, $nfd);
        $this->assertSame(\Normalizer::normalize($nfc, \Normalizer::FORM_C), (new SpeechPreprocessor())->clean($nfd));
    }

    public function test_markdown_and_emoji_are_removed(): void
    {
        $p = new SpeechPreprocessor();
        $this->assertSame('Remove infected leaves now.', $p->clean("- **Remove** infected _leaves_ now 🍂."));
        $this->assertSame('See the guide.', $p->clean('See [the guide](https://x.y).'));
    }

    public function test_script_splits_sentences_and_clauses_with_pauses(): void
    {
        $script = (new SpeechPreprocessor())->script([
            'observations'    => ['Ganyen sun yi ruwan ƙasa 🍂.'],
            'explanation'     => 'Wannan cutar ɓarna ce. Tana yaɗuwa da sauri, musamman lokacin damina.',
            'recommendations' => ['Fesa 2 L, sau 3.'],
            'safety_note'     => 'Wannan shawarar AI ce.',
        ], 'ha');

        $this->assertSame(['Ganyen sun yi ruwan ƙasa.', 'Wannan cutar ɓarna ce.', 'Tana yaɗuwa da sauri, musamman lokacin damina.', 'Fesa 2 L, sau 3.', 'Wannan shawarar AI ce.'], $script['sentences']);
        $this->assertSame([
            ['text' => 'Ganyen sun yi ruwan ƙasa.', 'sentence' => 0, 'pause_ms' => 700],
            ['text' => 'Wannan cutar ɓarna ce.', 'sentence' => 1, 'pause_ms' => 450],
            ['text' => 'Tana yaɗuwa da sauri,', 'sentence' => 2, 'pause_ms' => 250],
            ['text' => 'musamman lokacin damina.', 'sentence' => 2, 'pause_ms' => 700],
            ['text' => 'Fesa lita biyu,', 'sentence' => 3, 'pause_ms' => 250],
            ['text' => 'sau uku.', 'sentence' => 3, 'pause_ms' => 700],
            ['text' => 'Wannan shawarar A I ce.', 'sentence' => 4, 'pause_ms' => 0],
        ], $script['segments']);
    }

    public function test_no_segment_exceeds_200_characters(): void
    {
        $long = trim(str_repeat('abcdefghij ', 60));
        $script = (new SpeechPreprocessor())->script(['observations' => [$long . '.'], 'explanation' => '', 'recommendations' => [], 'safety_note' => ''], 'en');
        foreach ($script['segments'] as $seg) {
            $this->assertLessThanOrEqual(200, mb_strlen($seg['text']));
        }
        $this->assertSame($long . '.', implode(' ', array_column($script['segments'], 'text')));
    }
}
