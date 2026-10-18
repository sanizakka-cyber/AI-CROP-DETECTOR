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
        {--size= : Scan results in the pack, spread over the 4 scan types (default speech.go_live.pack_size, 25)}
        {--per-type= : Scan results per scan type instead of --size}
        {--scans= : Comma-separated diagnosis ids to use instead of choosing}
        {--title= : Pack title}';

    protected $description = 'Build a native-speaker review pack: real anonymised scan results, translated, spoken by each candidate voice (labelled A/B/C).';

    public function handle(ReviewPackBuilder $builder): int
    {
        $candidates = $this->option('candidate') ?: $this->defaults($this->argument('lang'));
        $scanIds = $this->option('scans') ? array_map('intval', explode(',', $this->option('scans'))) : null;
        try {
            $pack = $builder->build(
                $this->argument('lang'), $candidates,
                $this->option('per-type') !== null
                    ? max(1, (int) $this->option('per-type'))
                    : ReviewPackBuilder::quotasFor((int) ($this->option('size') ?: config('speech.go_live.pack_size', 25))),
                null,
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

    /**
     * If no candidates were given: the language's configured voice first, then
     * every other voice of a selectable (licensed, switched-on) provider for
     * the language, up to 6. YarnGPT2 counts only when SPEECH_YARNGPT_ENABLED.
     */
    private function defaults(string $lang): array
    {
        $out = [];
        $language = \App\Models\SpeechLanguage::findCode($lang);
        if ($language?->provider && $language->voice) {
            $out[] = "{$language->provider}:{$language->voice}";
        }
        foreach (\App\Services\Speech\Providers\ProviderRegistry::selectable() as $name => $p) {
            foreach ($p['voices'][$lang] ?? [] as $v) {
                $out[] = "{$name}:{$v}";
            }
        }
        return array_slice(array_values(array_unique($out)), 0, 6);
    }
}
