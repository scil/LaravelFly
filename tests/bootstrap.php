<?php

if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    define('AS_ROOT',true);
    echo "LaravelFly dir as root\n";
    $loader = require __DIR__ . '/../vendor/autoload.php';
} else {
    define('AS_ROOT',false);
    $loader = require __DIR__ . '/../../../../vendor/autoload.php';
}
$loader->addPsr4("Illuminate\\Tests\\", __DIR__."/../vendor/laravel/framework/tests/");
