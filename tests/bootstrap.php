<?php

// Suppress PHP 8.5's PDO::MYSQL_ATTR_SSL_CA deprecation warning that Laravel 11
// has not yet adapted for. This is cosmetic — once Laravel ships a Pdo\Mysql
// fix in a patch release, this can be removed.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Custom error handler to swallow specifically the PDO SSL constant deprecation
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if ($errno === E_DEPRECATED && str_contains($errstr, 'PDO::MYSQL_ATTR_SSL_CA')) {
        return true; // suppress
    }
    return false; // let everything else through
});

require __DIR__.'/../vendor/autoload.php';
