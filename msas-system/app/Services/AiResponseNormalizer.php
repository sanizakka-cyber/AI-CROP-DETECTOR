<?php

namespace App\Services;

/**
 * Turns a raw Claude chat reply (which the model formats with Markdown —
 * headers, **bold**, bullet lists — by default, since nothing in the /chat
 * prompt tells it not to) into a clean result both web and mobile can
 * render without ever showing a farmer a literal ##, **, or backtick.
 *
 * Returns two things from the SAME parse so simple and rich clients can
 * both use it without re-implementing this logic:
 *   - 'reply': flat plain text, symbols stripped, headings/bullets kept as
 *     readable lines — a safe drop-in replacement for the raw text every
 *     existing consumer already reads from this key.
 *   - 'sections': structured [{title, content, items}] blocks for a client
 *     that wants to render real headings/lists instead of one text blob.
 */
class AiResponseNormalizer
{
    public static function normalize(string $raw): array
    {
        // Code fences are never meaningful in a farming-assistant answer —
        // unwrap the content, drop the fence markers themselves.
        $text = preg_replace('/```[a-zA-Z0-9]*\n?/', '', $raw);
        $text = str_replace('```', '', $text);

        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $sections = [];
        $current = ['title' => null, 'content' => [], 'items' => []];

        $flush = function () use (&$sections, &$current) {
            if ($current['title'] !== null || $current['content'] || $current['items']) {
                $sections[] = [
                    'title'   => $current['title'],
                    'content' => trim(implode("\n", $current['content'])),
                    'items'   => $current['items'],
                ];
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // Heading: one or more leading # markers.
            if (preg_match('/^#{1,6}\s+(.+)$/', $trimmed, $m)) {
                $flush();
                $current = ['title' => self::stripInline($m[1]), 'content' => [], 'items' => []];
                continue;
            }
            // Bullet: -, *, or • followed by a space.
            if (preg_match('/^[-*•]\s+(.+)$/', $trimmed, $m)) {
                $current['items'][] = self::stripInline($m[1]);
                continue;
            }
            // Numbered list: "1. " / "1) ".
            if (preg_match('/^\d+[.)]\s+(.+)$/', $trimmed, $m)) {
                $current['items'][] = self::stripInline($m[1]);
                continue;
            }

            // A plain paragraph that follows this section's list items begins
            // a new block. Without this, flush() emits one section per parse
            // whose {content, items} always render content-then-items — so a
            // reply shaped "intro / list / recap / list / closing" collapses
            // into "intro+recap+closing" then every bullet, silently
            // reordering the answer. Starting a fresh section here keeps the
            // model's original paragraph↔list order intact for both clients.
            if ($current['items']) {
                $flush();
                $current = ['title' => null, 'content' => [], 'items' => []];
            }
            $current['content'][] = self::stripInline($trimmed);
        }
        $flush();

        // If the reply had no Markdown structure at all (a single plain
        // paragraph, the common case for a short answer), sections ends up
        // as one titleless block — that's correct, not an error.
        $blocks = [];
        foreach ($sections as $s) {
            $parts = [];
            if ($s['title'])   $parts[] = $s['title'];
            if ($s['content']) $parts[] = $s['content'];
            foreach ($s['items'] as $item) $parts[] = "• {$item}";
            $blocks[] = implode("\n", $parts);
        }
        $plain = implode("\n\n", $blocks);

        return ['reply' => $plain, 'sections' => $sections];
    }

    /** Strips inline emphasis/code markers, keeping the underlying words. */
    private static function stripInline(string $text): string
    {
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);   // **bold**
        $text = preg_replace('/__(.+?)__/', '$1', $text);        // __bold__
        $text = preg_replace('/(?<!\*)\*([^*]+?)\*(?!\*)/', '$1', $text); // *emphasis*
        $text = preg_replace('/`([^`]+)`/', '$1', $text);        // `code`
        return trim($text);
    }
}
