<?php

namespace App\Console\Commands;

use App\Services\Speech\ReviewPackBuilder;
use Illuminate\Console\Command;

/**
 * php artisan speech:build-review-pack ha \
 *     --candidate=yarngpt:hausa_female1 --candidate=yarngpt:hausa_male1 --candidate=openbible:openbible_hausa
 *
 * Needs the translator (AI engine) and every candidate's voice service
 * reachable. Run it on staging against a copy of production scans; it
 * stores anonymised copies only.
 */
class BuildSpeechReviewPack extends Command
{
    protected $signature = 'speech:build-review-pack
        {lang : Language code (ha, yo, ig)}
        {--candidate=* : provider:voice to compare (repeat; 1-6)}
        {--per-type=5 : Scan results per scan type (Plant/Crop, Livestock, Soil Sample, Pest ID)}
        {--scans= : Comma-separated diagnosis ids to use instead of choosing}
        {--title= : Pack title}';

    protected $description = 'Build a native-speaker review pack: real anonymised scan results, translated, spoken by each candidate voice (labelled A/B/C).';

    public function handle(ReviewPackBuilder $builder): int
    {
        $candidates = $this->option('candidate') ?: $this->defaults($this->argument('lang'));
        $scanIds = $this->option('scans') ? array_map('intval', explode(',', $this->option('scans'))) : null;
        try {
            $pack = $builder->build(
                $this->argument('lang'), $candidates, max(1, (int) $this->option('per-type')), null,
                $this->option('title'), $scanIds, fn ($line) => $this->line("  {$line}"),
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
        $this->info("Pack {$pack->id} \"{$pack->title}\": {$pack->items()->count()} items, " . count($pack->candidates) . ' voices.');
        $this->line('Reviewers open it at ' . route('admin.speech.review.pack', $pack));
        return self::SUCCESS;
    }

    /** Every YarnGPT2 voice listed for the language, if no candidates were given. */
    private function defaults(string $lang): array
    {
        return array_map(fn ($v) => "yarngpt:{$v}", config("speech.providers.yarngpt.voices.{$lang}", []));
    }
}
