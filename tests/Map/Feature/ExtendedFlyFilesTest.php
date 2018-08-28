<?php

namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Tests\BaseTestCase as Base;

class ExtendedFlyFilesTest extends Base
{

    var $partFileMap = [
        'extended/partfile/CookieJar.php' => '/vendor/laravel/framework/src/Illuminate/Cookie/CookieJar.php',
        'extended/partfile/CookieJarSame.php' => '/vendor/laravel/framework/src/Illuminate/Cookie/CookieJar.php',

        'extended/partfile/CookieSessionHandler.php' => '/vendor/laravel/framework/src/Illuminate/Session/CookieSessionHandler.php',
        'extended/partfile/DatabaseSessionHandler.php' => '/vendor/laravel/framework/src/Illuminate/Session/DatabaseSessionHandler.php',

        'extended/partfile/Compiler.php' => '/vendor/laravel/framework/src/Illuminate/View/Compilers/Compiler.php',
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

    function testPart()
    {
        $this->assertEquals(5, count($this->partFileMap));
        foreach ($this->partFileMap as $partFile => $offcial) {
            $partFile = static::$backOfficalDir . $partFile;
            $offcial = static::$laravelAppRoot . $offcial;
            $parts = explode('===A===', file_get_contents($partFile));
            $full = file_get_contents($offcial);
            $full = preg_replace('/\s+/',' ',$full);
            foreach ($parts as $part) {
                $this->difOnePart($part, $full, $partFile);
            }

        }

    }

    function difOnePart($part, $full,$file)
    {
        $part = preg_replace('/\s+/',' ',$part);

        $pos = strpos($full,$part);
        // var_dump($pos);
        self::assertNotFalse($pos,"$part\n\nin:\n$file");
    }

    function testFiles()
    {
        $this->assertEquals(19, count($this->map));
    }

    function testCompareFilesContent()
    {

        $this->compareFilesContent($this->map);
    }
}