<?php

/*
| "Listen to your result": translated, spoken scan results.
|
| Languages, voices, providers and rates are managed in the admin screen
| (table speech_languages). The `languages` list below only seeds that
| table the first time; after that the database is the source of truth.
*/

return [

    // Jobs run on their own queue so the rest of the app keeps QUEUE_CONNECTION
    // (sync in production). docker/start.sh runs a worker for this queue only.
    'queue_connection' => env('SPEECH_QUEUE_CONNECTION', 'database'),
    'queue'            => env('SPEECH_QUEUE', 'speech'),

    // Where generated audio is stored. An S3-compatible disk (r2) is served by
    // short-lived signed URLs from the bucket; any other disk is streamed by
    // a signed Laravel route.
    'disk' => env('SPEECH_DISK', env('R2_BUCKET') ? 'r2' : 'local'),

    // Prepare text + audio in the farmer's saved (preferred) language right
    // after each scan, so Listen plays at once. Decision 2026-10-18: on, but
    // only for a live language with audio switched on (English and Hausa at
    // launch). Queued after the scan is saved, so it never slows the result;
    // until the audio exists the app shows the text and "Audio is being prepared".
    'pregenerate' => (bool) env('SPEECH_PREGENERATE', true),

    'default_rate'      => 0.9,   // calm, not rushed
    'max_segment_chars' => 200,
    'pause_comma_ms'    => 250,
    'pause_stop_ms'     => 450,
    'audio_format'      => 'mp3',
    'bitrate_kbps'      => 40,    // 32-48 kbps mono for low-bandwidth users

    // Legacy quick check ("Mark translation reviewed" on a result). Shown for
    // information only; going live is decided by go_live below.
    'review_threshold' => 50,

    // Go-live rule per language (admin cannot set a language live until it
    // passes). Reviews come from the review packs in /admin/speech/review.
    'go_live' => [
        // Decision 2026-10-18: review packs of 25 scan results; a language
        // needs 2 approved packs. A pack is approved when every scan result in
        // it has been reviewed by each required reviewer kind, its average
        // accuracy (and naturalness, if the language has audio) is at least
        // the minimum, and it has no dose/chemical error.
        'pack_size'            => (int) env('SPEECH_REVIEW_PACK_SIZE', 25),
        'min_approved_packs'   => (int) env('SPEECH_GO_LIVE_MIN_PACKS', 2),
        // Scan results reviewed, across all scan types (2 packs x 25).
        'min_reviewed_results' => (int) env('SPEECH_GO_LIVE_MIN_RESULTS', 50),
        // Each of these scan types needs at least min_per_scan_type reviewed results.
        'scan_types'           => ['crop', 'livestock', 'soil', 'pest'],
        'min_per_scan_type'    => (int) env('SPEECH_GO_LIVE_MIN_PER_TYPE', 5),
        // A result counts as reviewed once it has a review from each of these
        // reviewer kinds (native speaker AND extension officer / vet).
        'reviewer_kinds'       => array_filter(explode(',', env('SPEECH_GO_LIVE_REVIEWER_KINDS', 'native,expert'))),
        // Average of every review of a scan result, 1-5. Naturalness applies
        // only to a language with audio switched on: a text-only language
        // goes live on its translation alone.
        'min_accuracy'         => (float) env('SPEECH_GO_LIVE_MIN_ACCURACY', 4.0),
        'min_naturalness'      => (float) env('SPEECH_GO_LIVE_MIN_NATURALNESS', 4.0),
        // Dose/amount/unit or chemical/medicine/product errors marked by any reviewer, on any item.
        'max_dosage_errors'    => (int) env('SPEECH_GO_LIVE_MAX_DOSAGE_ERRORS', 0),
    ],

    // Roles that may mark a translation reviewed (and read any scan's translation to do so).
    'reviewer_roles' => ['extension-officer', 'agronomist', 'admin', 'ceo'],
    'admin_roles'    => ['admin', 'ceo'],

    'safety_note_en' => 'This is AI guidance and may be wrong. Confirm with an extension officer or vet before using chemicals or medicines.',

    'translator' => [
        'timeout' => 60,
    ],

    // Every provider the admin can choose, with its license. Providers whose
    // license does not allow commercial use cannot be selected.
    'providers' => [
        'yarngpt' => [
            'label' => 'YarnGPT2 (self-hosted)', 'driver' => 'self_hosted', 'engine' => 'yarngpt',
            'license' => 'Apache-2.0', 'commercial_use' => true,
            // Not used in production on CPU (about 5 minutes to the first
            // sentence, about an hour per Hausa result; tts-service/bench/).
            // Switch on only where its service runs on a GPU (the Yoruba/Igbo GPU test).
            'enabled' => (bool) env('SPEECH_YARNGPT_ENABLED', false),
            'source' => 'https://huggingface.co/saheedniyi/YarnGPT2',
            'languages' => ['en', 'ha', 'yo', 'ig'],
            'voices' => [
                'en' => ['idera', 'chinenye', 'jude', 'emma', 'umar', 'joke', 'zainab', 'osagie', 'remi', 'tayo'],
                'yo' => ['yoruba_male2', 'yoruba_female2', 'yoruba_female1'],
                'ig' => ['igbo_female2', 'igbo_male2', 'igbo_female1'],
                'ha' => ['hausa_female1', 'hausa_female2', 'hausa_male2', 'hausa_male1'],
            ],
        ],
        'farmerline' => [
            'label' => 'FarmerlineML Hausa (self-hosted)', 'driver' => 'self_hosted', 'engine' => 'farmerline',
            'license' => 'CC-BY-4.0', 'commercial_use' => true, 'credit' => 'Hausa voice: FarmerlineML',
            // Off until Farmerline confirms in writing that the model is not derived
            // from facebook/mms-tts-hau (CC-BY-NC) and that commercial use is allowed.
            'enabled' => (bool) env('SPEECH_FARMERLINE_CONFIRMED', false),
            'source' => 'https://huggingface.co/FarmerlineML/main_hausa_TTS',
            'license_note' => 'Model card does not state the base model. Confirm it is not derived from a CC-BY-NC model (e.g. facebook/mms-tts-hau) before enabling.',
            'languages' => ['ha'],
            'voices' => ['ha' => ['farmerline_hausa']],
        ],
        'openbible' => [
            'label' => 'OpenBible VITS (self-hosted)', 'driver' => 'self_hosted', 'engine' => 'openbible',
            'license' => 'CC-BY-SA-4.0', 'commercial_use' => true,
            'credit' => 'Voices: OpenBibleTTS (McGill-NLP, Mila), trained from scratch on Open Bible recordings (CC BY-SA 4.0)',
            'source' => 'https://huggingface.co/multilingual-tts',
            'license_note' => 'Trained from scratch on CC BY-SA Open Bible audio. Attribution required; changed model weights must be shared under CC BY-SA. One voice per language (no speaker embedding), Bible-reading style; weaker on non-Bible text per the OpenBibleTTS paper.',
            'languages' => ['ha', 'yo', 'ig'],
            'voices' => ['ha' => ['openbible_hausa'], 'yo' => ['openbible_yoruba'], 'ig' => ['openbible_igbo']],
            // The CPU speech container (tts-service/Dockerfile.openbible), shared with Kokoro.
            'url' => env('TTS_CPU_URL', env('TTS_OPENBIBLE_URL')),
            'key' => env('TTS_CPU_KEY', env('TTS_OPENBIBLE_KEY')),
        ],
        'kokoro' => [
            'label' => 'Kokoro-82M English (self-hosted)', 'driver' => 'self_hosted', 'engine' => 'kokoro',
            'license' => 'Apache-2.0', 'commercial_use' => true,
            'credit' => 'English voice: Kokoro-82M by hexgrad (Apache License 2.0)',
            'source' => 'https://huggingface.co/hexgrad/Kokoro-82M',
            'license_note' => 'Trained on permissive/non-copyrighted audio per the model card. American and British accents only (no Nigerian English voice).',
            'languages' => ['en'],
            // The best-graded English voices in the model card's VOICES.md.
            'voices' => ['en' => ['af_heart', 'af_bella', 'bf_emma']],
            'url' => env('TTS_CPU_URL', env('TTS_OPENBIBLE_URL')),
            'key' => env('TTS_CPU_KEY', env('TTS_OPENBIBLE_KEY')),
        ],
        'elevenlabs' => [
            'label' => 'ElevenLabs (cloud)', 'driver' => 'elevenlabs',
            'license' => 'Commercial API (paid)', 'commercial_use' => true,
            'languages' => [], // set per language in admin after confirming the provider supports it
            'voices' => [],
        ],
    ],

    'self_hosted' => [
        'url'     => env('TTS_SERVICE_URL', 'http://127.0.0.1:8002'),
        'key'     => env('TTS_SERVICE_KEY'),
        'timeout' => (int) env('TTS_SERVICE_TIMEOUT', 150),
    ],

    'elevenlabs' => [
        'key'           => env('ELEVENLABS_API_KEY'),
        'model'         => env('ELEVENLABS_MODEL'),
        'output_format' => env('ELEVENLABS_OUTPUT_FORMAT', 'mp3_22050_32'),
    ],

    // Seed for speech_languages (first migration only). Launch voices
    // (decision 2026-10-18): English Kokoro, Hausa OpenBible; Yoruba and Igbo
    // keep their OpenBible voices as review candidates, with audio off
    // (audio_enabled is set by migration 2026_10_18_100001).
    'languages' => [
        ['code' => 'en', 'name_english' => 'English', 'name_native' => 'English', 'status' => 'live',
         'provider' => 'kokoro', 'voice' => 'af_heart', 'sort' => 1],
        ['code' => 'ha', 'name_english' => 'Hausa', 'name_native' => 'Hausa', 'status' => 'in_review',
         'provider' => 'openbible', 'voice' => 'openbible_hausa', 'sort' => 2],
        ['code' => 'yo', 'name_english' => 'Yoruba', 'name_native' => 'Yorùbá', 'status' => 'in_review',
         'provider' => 'openbible', 'voice' => 'openbible_yoruba', 'sort' => 3],
        ['code' => 'ig', 'name_english' => 'Igbo', 'name_native' => 'Igbo', 'status' => 'in_review',
         'provider' => 'openbible', 'voice' => 'openbible_igbo', 'sort' => 4],
    ],
];
