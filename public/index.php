<?php

use Illuminate\Http\Request;

// Suppress PHP 8.5 deprecation about PDO::MYSQL_ATTR_SSL_CA in Laravel's
// framework config — Laravel hasn't patched upstream yet. This is cosmetic only.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
