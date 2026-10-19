<?php

/*
| Scan error reporting (App\Services\Scan\ScanErrorReporter).
*/
return [
    // Roles that see the "Technical details" box (code, status, service,
    // masked message) on a failed scan, and the /admin/scan-errors page.
    'staff_roles' => ['admin', 'ceo', 'customer-support'],

    // Email the owner when ENGINE_*, AI_PROVIDER_* or INTERNAL_ERROR failures
    // reach `alert_threshold` within `alert_window_minutes`; at most one
    // email per `alert_cooldown_minutes`. Recipients: SCAN_ERROR_ALERT_EMAILS
    // (comma separated), otherwise every active CEO account.
    'alert_emails'           => array_filter(array_map('trim', explode(',', (string) env('SCAN_ERROR_ALERT_EMAILS', '')))),
    'alert_threshold'        => (int) env('SCAN_ERROR_ALERT_THRESHOLD', 5),
    'alert_window_minutes'   => (int) env('SCAN_ERROR_ALERT_WINDOW', 10),
    'alert_cooldown_minutes' => (int) env('SCAN_ERROR_ALERT_COOLDOWN', 30),
];
