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

    // Prepare text + audio in the farmer's saved language right after each scan,
    // so Listen plays at once. Off by default: one translation + one synthesis per scan.
    'pregenerate' => (bool) env('SPEECH_PREGENERATE', false),

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
        // Scan results reviewed, across all scan types.
        'min_reviewed_results' => (int) env('SPEECH_GO_LIVE_MIN_RESULTS', 50),
        // Each of these scan types needs at least min_per_scan_type reviewed results.
        'scan_types'           => ['crop', 'livestock', 'soil', 'pest'],
        'min_per_scan_type'    => (int) env('SPEECH_GO_LIVE_MIN_PER_TYPE', 5),
        // A result counts as reviewed once it has a review from each of these
        // reviewer kinds (native speaker AND extension officer / vet).
        'reviewer_kinds'       => array_filter(explode(',', env('SPEECH_GO_LIVE_REVIEWER_KINDS', 'native,expert'))),
        // Average of every review of a scan result, 1-5.
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
            // Its own container (tts-service/Dockerfile.openbible): coqui-tts and YarnGPT2 cannot share one.
            'url' => env('TTS_OPENBIBLE_URL'),
            'key' => env('TTS_OPENBIBLE_KEY'),
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

    // Seed for speech_languages (first migration only). Default voices are the
    // first listed per language on the YarnGPT2 card ("in order of performance
    // and stability").
    'languages' => [
        ['code' => 'en', 'name_english' => 'English', 'name_native' => 'English', 'status' => 'live',
         'provider' => 'yarngpt', 'voice' => 'idera', 'sort' => 1],
        ['code' => 'ha', 'name_english' => 'Hausa', 'name_native' => 'Hausa', 'status' => 'in_review',
         'provider' => 'yarngpt', 'voice' => 'hausa_female1', 'sort' => 2],
        ['code' => 'yo', 'name_english' => 'Yoruba', 'name_native' => 'Yorùbá', 'status' => 'in_review',
         'provider' => 'yarngpt', 'voice' => 'yoruba_male2', 'sort' => 3],
        ['code' => 'ig', 'name_english' => 'Igbo', 'name_native' => 'Igbo', 'status' => 'in_review',
         'provider' => 'yarngpt', 'voice' => 'igbo_female2', 'sort' => 4],
    ],
];
