<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$nebulaBasePath = dirname(__DIR__);

// Fresh ZIP extracts (or some hosts) may omit empty storage dirs; Laravel's default
// view config uses realpath() on storage/framework/views — if missing, Blade gets no cache path.
$nebulaStorageDirs = [
    $nebulaBasePath.'/storage/framework/cache/data',
    $nebulaBasePath.'/storage/framework/sessions',
    $nebulaBasePath.'/storage/framework/views',
    $nebulaBasePath.'/storage/framework/testing',
    $nebulaBasePath.'/storage/logs',
];
foreach ($nebulaStorageDirs as $nebulaDir) {
    if (! is_dir($nebulaDir)) {
        @mkdir($nebulaDir, 0755, true);
    }
}

// Log PHP fatal errors that occur before Laravel's exception handler runs (otherwise
// the site returns 500 with nothing in laravel.log — common after partial deploys).
$nebulaFatalLog = $nebulaBasePath.'/storage/logs/php-fatal.log';
register_shutdown_function(function () use ($nebulaFatalLog) {
    $e = error_get_last();
    if (! $e || ! in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    $line = sprintf("[%s] %s in %s:%d\n", date('c'), $e['message'], $e['file'] ?? '', $e['line'] ?? 0);
    @file_put_contents($nebulaFatalLog, $line, FILE_APPEND | LOCK_EX);
});

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $nebulaBasePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $nebulaBasePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $nebulaBasePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
