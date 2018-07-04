<?php

namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Tests\Map\MapTestCase;

class ExtendedFlyFilesTest extends MapTestCase
{


    var $map = [

        'extended/Dispatcher.php' => '/vendor/laravel/framework/src/Illuminate/Events/Dispatcher.php',
        'extended/EventServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Events/EventServiceProvider.php',


        'extended/Gate.php' => '/vendor/laravel/framework/src/Illuminate/Auth/Access/Gate.php',
        'extended/AuthServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Auth/AuthServiceProvider.php',
        'extended/AuthManager.php' => '/vendor/laravel/framework/src/Illuminate/Auth/AuthManager.php',


        'extended/CookieJar.php' => '/vendor/laravel/framework/src/Illuminate/Cookie/CookieJar.php',
        'extended/CookieServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Cookie/CookieServiceProvider.php',

        'extended/Connectors/ConnectionFactory.php' => '/vendor/laravel/framework/src/Illuminate/Database/Connectors/ConnectionFactory.php',
        'extended/Connectors/MySqlConnector.php' => '/vendor/laravel/framework/src/Illuminate/Database/Connectors/MySqlConnector.php',
        'extended/DatabaseManager.php' => '/vendor/laravel/framework/src/Illuminate/Database/DatabaseManager.php',
        'extended/DatabaseServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Database/DatabaseServiceProvider.php',
        'extended/MySqlConnection.php' => '/vendor/laravel/framework/src/Illuminate/Database/MySqlConnection.php',


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
        $this->assertEquals(21, count($this->map));
    }

    function testCompareFilesContent()
    {

        $this->compareFilesContent($this->map);
    }
}