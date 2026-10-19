<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Http;

/**
 * A working AI engine for tests that need a completed scan. Before scan
 * error codes, a missing engine still produced an "expert will review"
 * record, and many tests leaned on that; a failed scan now creates no record.
 */
trait FakesAiEngine
{
    protected function fakeAiEngine(array $result = [], string $base = 'http://engine.test', array $more = []): void
    {
        config(['services.ai_engine.url' => $base, 'services.ai_engine.key' => 'test-engine-key']);
        Http::fake(array_merge($more, [
            "{$base}/health"    => Http::response(['status' => 'ok']),
            "{$base}/predict/*" => Http::response(array_merge([
                'contract_version' => '2026-10-phase11', 'assessment_outcome' => 'assessed', 'subject_present' => true,
                'image_quality' => 'good', 'image_issues' => [], 'photo_tips' => [], 'alternative_findings' => [],
                'disease' => 'Late blight', 'confidence' => 80, 'confidence_valid' => true,
                'severity' => 'Moderate', 'urgency' => 'Medium',
            ], $result)),
        ]));
    }
}
