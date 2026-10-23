<?php

/*
| QA test accounts (staging only).
|
| Dedicated QA accounts (users.is_qa) get their own scan quota so testing
| never uses personal accounts or eats a real farmer's limit. This can NEVER
| be on in production: the flag is forced off when APP_ENV=production,
| whatever QA_ACCOUNTS_ENABLED says, and qa:create-account refuses to run there.
*/

$production = env('APP_ENV') === 'production';

return [
    'enabled'    => !$production && (bool) env('QA_ACCOUNTS_ENABLED', false),
    'scan_limit' => (int) env('QA_SCAN_LIMIT', 200),
    // Staging (Phase 12.2): only QA accounts may scan, so a stray sign-up on
    // the staging URL cannot spend AI credit. Also forced off in production.
    'scans_qa_only' => !$production && (bool) env('QA_SCANS_QA_ONLY', false),
];
