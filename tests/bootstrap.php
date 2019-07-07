<?php

//if (is_file(__DIR__ . '/../vendor/autoload.php')) {
//    $AT_FLY_ROOT = true;
//    $loader = __DIR__ . '/../vendor/autoload.php';
//} else {
//    $AT_FLY_ROOT = false;
//}

if (!class_exists('Swoole\Channel')) {
    $info = <<<ABC
    
No class Swoole\Channel.
Please install php ext swoole_async https://github.com/swoole/ext-async
or use older versions of swoole: pecl install swoole-4.2.13
ABC;
    die($info);
}


if (isset($_ENV['LARAVEL_PROJECT_ROOT'])) {
    define('LARAVEL_APP_ROOT', $_ENV['LARAVEL_PROJECT_ROOT']);
    $loader = LARAVEL_APP_ROOT . '/vendor/autoload.php';
} else {
    define('LARAVEL_APP_ROOT', dirname(__DIR__, $AT_FLY_ROOT ? 4 : 1));
    $loader = __DIR__ . '/../../../../vendor/autoload.php';
}

# drop __DIR__　to avoid the situation that laravel-fly is at a symlink dir
# define('FLY_ROOT', dirname(__DIR__,1));
define('FLY_ROOT', LARAVEL_APP_ROOT . '/vendor/scil/laravel-fly');

define('DEFAULT_SERVER_CONFIG_FILE', FLY_ROOT . '/config/laravelfly-server-config.example.php');

assert(is_dir(LARAVEL_APP_ROOT));
echo "laravel project is at " . LARAVEL_APP_ROOT . "\n";

if ( is_file( $beforeapp= LARAVEL_APP_ROOT . '/bootstrap/app-before.php')){
    echo "require $beforeapp\n";
    require $beforeapp;
}

assert(is_file(DEFAULT_SERVER_CONFIG_FILE));
echo "default SERVER_CONFIG_FILE " . DEFAULT_SERVER_CONFIG_FILE . "\n";

assert(is_file($loader));
echo "loader is from $loader\n\n";
$loader = require $loader;
$loader->addPsr4("Illuminate\\Tests\\", LARAVEL_APP_ROOT . "/vendor/laravel/framework/tests/");


require_once __DIR__ . "/swoole_src_tests/include/swoole.inc";
require_once __DIR__ . "/swoole_src_tests/include/lib/curl_concurrency.php";
