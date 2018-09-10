<?php

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    $AT_FLY_ROOT = true;
    $loader = require __DIR__ . '/../vendor/autoload.php';
} else {
    $AT_FLY_ROOT = false;
    $loader = require __DIR__ . '/../../../../vendor/autoload.php';
}

if (isset($_ENV['LARAVEL_PROJECT_ROOT'])) {
    define('LARAVEL_APP_ROOT', $_ENV['LARAVEL_PROJECT_ROOT']);
} else {
    define('LARAVEL_APP_ROOT', dirname(__DIR__, $AT_FLY_ROOT ? 4 : 1));
}

echo "laravel project is at " . LARAVEL_APP_ROOT . "\n";

$loader->addPsr4("Illuminate\\Tests\\", __DIR__ . "/../vendor/laravel/framework/tests/");
