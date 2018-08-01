<?php

namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Tests\Map\MapTestCase;

class ExtendedFlyFilesTest extends MapTestCase
{

    var $partFileMap = [
        'extended/partfile/CookieJar.php' => '/vendor/laravel/framework/src/Illuminate/Cookie/CookieJar.php',
        'extended/partfile/CookieJarSame.php' => '/vendor/laravel/framework/src/Illuminate/Cookie/CookieJar.php',

        'extended/partfile/CookieSessionHandler.php' => '/vendor/laravel/framework/src/Illuminate/Session/CookieSessionHandler.php',
    ];

    var $map = [

        'extended/Dispatcher.php' => '/vendor/laravel/framework/src/Illuminate/Events/Dispatcher.php',
        'extended/EventServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Events/EventServiceProvider.php',

        'extended/CookieJar.php' => '/vendor/laravel/framework/src/Illuminate/Cookie/CookieJar.php',

        'extended/Gate.php' => '/vendor/laravel/framework/src/Illuminate/Auth/Access/Gate.php',
        'extended/AuthServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Auth/AuthServiceProvider.php',
        'extended/AuthManager.php' => '/vendor/laravel/framework/src/Illuminate/Auth/AuthManager.php',


        'extended/CookieServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Cookie/CookieServiceProvider.php',

        'extended/ConnectionFactory.php' => '/vendor/hhxsv5/laravel-s/src/Illuminate/Database/ConnectionFactory.php',
        'extended/DatabaseManager.php' => '/vendor/hhxsv5/laravel-s/src/Illuminate/Database/DatabaseManager.php',
        'extended/DatabaseServiceProvider.php' => '/vendor/hhxsv5/laravel-s/src/Illuminate/Database/DatabaseServiceProvider.php',

        'extended/CookieSessionHandler.php' => '/vendor/laravel/framework/src/Illuminate/Session/CookieSessionHandler.php',
        'extended/DatabaseSessionHandler.php' => '/vendor/laravel/framework/src/Illuminate/Session/DatabaseSessionHandler.php',
        'extended/SessionManager.php' => '/vendor/laravel/framework/src/Illuminate/Session/SessionManager.php',
        'extended/SessionServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Session/SessionServiceProvider.php',
        'extended/StartSession.php' => '/vendor/laravel/framework/src/Illuminate/Session/Middleware/StartSession.php',


        'extended/Compiler.php' => '/vendor/laravel/framework/src/Illuminate/View/Compilers/Compiler.php',
        'extended/BladeCompiler.php' => '/vendor/laravel/framework/src/Illuminate/View/Compilers/BladeCompiler.php',
        'extended/Factory.php' => '/vendor/laravel/framework/src/Illuminate/View/Factory.php',
        'extended/ViewServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/View/ViewServiceProvider.php',

    ];


    function testFiles()
    {
        $this->assertEquals(19, count($this->map));
    }

    function testCompareFilesContent()
    {

        $this->compareFilesContent($this->map);
    }
}