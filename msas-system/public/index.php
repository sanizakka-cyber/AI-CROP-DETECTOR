<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| cPanel deployment path resolution
|--------------------------------------------------------------------------
| On cPanel, if this index.php is inside public_html/ and the rest of the
| Laravel project is one level up (e.g. ~/msas_system/), the __DIR__/..
| paths below will resolve to ~/msas_system/ automatically.
|
| Standard deployment (document root = project/public/):
|   __DIR__/../vendor/autoload.php  →  project/vendor/autoload.php  ✓
|
| cPanel deployment (public_html/ contains public/* files, project is ~/msas_system/):
|   __DIR__/../vendor/autoload.php  →  ~/vendor/autoload.php  ✗
|   Fix: upload project to ~/msas_system/ then set APP_BASE_PATH below.
|--------------------------------------------------------------------------
*/

// If running from public_html/ on cPanel, set this to the absolute path of
// the project directory (one level above public_html):
//   $overrideBasePath = '/home/YOUR_CPANEL_USER/msas_system';
// Otherwise leave null to use the standard relative path.
$overrideBasePath = null;

$basePath = rtrim($overrideBasePath ?? dirname(__DIR__), '/');

if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $basePath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
