<?php

/*
|--------------------------------------------------------------------------
| Legal agreements: versions, minimum age and enforcement switches
|--------------------------------------------------------------------------
|
| Bump a version whenever the wording of that document changes in a way
| users must agree to again. Users whose recorded version differs from the
| current one are asked to accept the new version at their next web login
| (see EnsureCurrentLegalAcceptance middleware).
|
*/

return [

    // Users must be at least this old to register (Terms §3; under the
    // Nigeria Data Protection Act 2023 a person below 18 is a child, whose
    // data would need verified parental consent, so children are not accepted).
    'minimum_age' => 18,

    'versions' => [
        'terms'   => env('LEGAL_TERMS_VERSION', '2026-11-v2'),
        'privacy' => env('LEGAL_PRIVACY_VERSION', '2026-11-v2'),
        'refund'  => env('LEGAL_REFUND_VERSION', '2026-11-v2'),
    ],

    // Require terms + age on the mobile registration API. App builds older
    // than v1.4.11 do not send these fields; set this to false temporarily
    // only if those builds must keep registering until the new app is out.
    'require_api_consent' => env('LEGAL_REQUIRE_API_CONSENT', true),

    // Require the "no refund" acknowledgement before a subscription payment
    // starts on the API (older app builds do not send it yet).
    'require_api_refund_ack' => env('LEGAL_REQUIRE_API_REFUND_ACK', true),

    // Ask signed-in web users to accept new Terms/Privacy versions.
    'enforce_reacceptance' => env('LEGAL_ENFORCE_REACCEPTANCE', true),
];
