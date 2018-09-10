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
    define('FLY_ROOT', dirname(__DIR__,1));
}

const DEFAULT_SERVER_CONFIG_FILE = __DIR__ . '/../config/laravelfly-server-config.example.php';

echo "laravel project is at " . LARAVEL_APP_ROOT . "\n";
echo "default SERVER_CONFIG_FILE " . DEFAULT_SERVER_CONFIG_FILE. "\n";

$loader->addPsr4("Illuminate\\Tests\\", __DIR__ . "/../vendor/laravel/framework/tests/");
