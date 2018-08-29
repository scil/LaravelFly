<?php

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    $AS_ROOT = true;
    $loader = require __DIR__ . '/../vendor/autoload.php';
} else {
    $AS_ROOT = false;
    $loader = require __DIR__ . '/../../../../vendor/autoload.php';
}

define('WORKING_ROOT', $AS_ROOT ? dirname(__DIR__) :
    dirname(__DIR__, 4));


if (isset($_ENV['LARAVEL_PROJECT'])) {
    define('LARAVEL_APP_ROOT', $_ENV['LARAVEL_PROJECT']);
} else {
    define('LARAVEL_APP_ROOT',
        $AS_ROOT ? dirname(WORKING_ROOT, 3) : WORKING_ROOT);
}

echo "laravel project is at " . LARAVEL_APP_ROOT . "\n";

$loader->addPsr4("Illuminate\\Tests\\", __DIR__ . "/../vendor/laravel/framework/tests/");
