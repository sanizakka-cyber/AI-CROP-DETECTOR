<?php

namespace Tests\Unit;

use App\Services\AiResponseNormalizer;
use PHPUnit\Framework\TestCase;

class AiResponseNormalizerTest extends TestCase
{
    public function test_strips_markdown_symbols_from_flat_reply(): void
    {
        $out = AiResponseNormalizer::normalize("## Diagnosis\nLikely **nitrogen** deficiency. Apply `urea`.");

        $this->assertStringNotContainsString('#', $out['reply']);
        $this->assertStringNotContainsString('**', $out['reply']);
        $this->assertStringNotContainsString('`', $out['reply']);
        $this->assertStringContainsString('nitrogen deficiency', $out['reply']);
    }

    public function test_replaces_em_and_en_dashes(): void
    {
        $out = AiResponseNormalizer::normalize(
            "Which leaves are yellow \u{2014} the older ones \u{2013} or all over?\n"
            . "- Nitrogen deficiency \u{2014} apply urea\n"
            . "\u{2014} a dash-led line becomes a real bullet"
        );

        $this->assertStringNotContainsString("\u{2014}", $out['reply']);
        $this->assertStringNotContainsString("\u{2013}", $out['reply']);
        foreach ($out['sections'] as $s) {
            $this->assertStringNotContainsString("\u{2014}", $s['content']);
            foreach ($s['items'] as $item) {
                $this->assertStringNotContainsString("\u{2014}", $item);
            }
        }
        $this->assertStringContainsString('yellow, the older ones, or all over?', $out['reply']);
        // A line that started with an em-dash is treated as a list item.
        $this->assertContains('a dash-led line becomes a real bullet', $out['sections'][0]['items']);
    }

    /**
     * A reply shaped "intro / list / recap / list / closing" must render in
     * that order. The normalizer used to fold a heading-less reply into ONE
     * section whose {content, items} always render content-then-items, which
     * silently hoisted every later paragraph above every bullet.
     */
    public function test_preserves_paragraph_and_list_document_order(): void
    {
        $raw = <<<'MD'
        Yellowing leaves can have several causes. Tell me:

        - Which leaves yellow first?
        - How old is the crop?

        Common causes in the field:

        - Nitrogen deficiency — apply urea.
        - Waterlogging — improve drainage.

        Share more detail and I can narrow it down.
        MD;

        $out = AiResponseNormalizer::normalize($raw);

        $this->assertCount(3, $out['sections']);
        $this->assertSame('Yellowing leaves can have several causes. Tell me:', $out['sections'][0]['content']);
        $this->assertCount(2, $out['sections'][0]['items']);
        $this->assertSame('Common causes in the field:', $out['sections'][1]['content']);
        $this->assertCount(2, $out['sections'][1]['items']);
        $this->assertSame('Share more detail and I can narrow it down.', $out['sections'][2]['content']);
        $this->assertSame([], $out['sections'][2]['items']);

        // The closing sentence must appear AFTER the last bullet in the flat reply.
        $this->assertGreaterThan(
            strpos($out['reply'], 'Waterlogging'),
            strpos($out['reply'], 'Share more detail'),
        );
    }

    public function test_plain_paragraph_stays_a_single_section(): void
    {
        $out = AiResponseNormalizer::normalize('The maize needs nitrogen. Apply urea within two weeks.');

        $this->assertCount(1, $out['sections']);
        $this->assertNull($out['sections'][0]['title']);
        $this->assertSame([], $out['sections'][0]['items']);
        $this->assertSame('The maize needs nitrogen. Apply urea within two weeks.', $out['reply']);
    }

    public function test_empty_reply_yields_no_sections(): void
    {
        $out = AiResponseNormalizer::normalize('');

        $this->assertSame([], $out['sections']);
        $this->assertSame('', $out['reply']);
    }
}
