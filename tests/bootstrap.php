<?php

//if (is_file(__DIR__ . '/../vendor/autoload.php')) {
//    $AT_FLY_ROOT = true;
//    $loader = __DIR__ . '/../vendor/autoload.php';
//} else {
//    $AT_FLY_ROOT = false;
//}

if (isset($_ENV['LARAVEL_PROJECT_ROOT'])) {
    define('LARAVEL_APP_ROOT', $_ENV['LARAVEL_PROJECT_ROOT']);
    $loader = LARAVEL_APP_ROOT.'/vendor/autoload.php';
} else {
    define('LARAVEL_APP_ROOT', dirname(__DIR__, $AT_FLY_ROOT ? 4 : 1));
    $loader = __DIR__ . '/../../../../vendor/autoload.php';
}

define('FLY_ROOT', dirname(__DIR__,1));

define('DEFAULT_SERVER_CONFIG_FILE',  dirname(__DIR__) . '/config/laravelfly-server-config.example.php');

echo "laravel project is at " . LARAVEL_APP_ROOT . "\n";
echo "default SERVER_CONFIG_FILE " . DEFAULT_SERVER_CONFIG_FILE. "\n";
echo "loader is $loader\n\n";

$loader = require $loader;
$loader->addPsr4("Illuminate\\Tests\\", __DIR__ . "/../vendor/laravel/framework/tests/");


require_once __DIR__ . "/swoole_src_tests/include/swoole.inc";
require_once __DIR__ . "/swoole_src_tests/include/lib/curl_concurrency.php";
