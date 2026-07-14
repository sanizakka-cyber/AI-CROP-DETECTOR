<?php
// One-time cache clearing script — DELETE THIS FILE AFTER RUNNING
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$results = [];
foreach (['route:clear', 'config:clear', 'cache:clear', 'view:clear'] as $cmd) {
    $kernel->call($cmd);
    $results[] = "✓ php artisan $cmd";
}

// Self-delete after running
@unlink(__FILE__);

echo "<pre style='font-family:monospace;font-size:14px;padding:20px;background:#1e1e1e;color:#4ade80'>";
echo implode("\n", $results);
echo "\n\n✓ Cache cleared. This file has been deleted automatically.";
echo "</pre>";
