<?php

namespace LaravelFly\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Base
 * @package LaravelFly\Tests
 *
 * why abstract? stop phpunit to use this testcase
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    static private $laravelApp;

    /**
     * @var EventDispatcher
     */
    static protected $dispatcher;

    /**
     * @var \LaravelFly\Server\ServerInterface
     */
    static protected $server;

    /**
     * @var string
     */
    static protected $root;

    static function setUpBeforeClass()
    {
        static::$root = realpath(__DIR__ . '/../../../..');
    }

    static protected function getLaravelApp()
    {
        if (!static::$laravelApp)
            static::$laravelApp = require_once static::$root . '/bootstrap/app.php';

        return static::$laravelApp;
    }

    static protected function makeServer( $constances = [], $options=[], $config_file =  __DIR__ . '/../config/laravelfly-server-config.example.php')
    {
        foreach ($constances as $name=>$val){
            define($name, $val);
        }

        $file_options = require $config_file;

        $options = array_merge($file_options, $options);

        $fly= \LaravelFly\Fly::init($options);

        static::$dispatcher = $fly::getDispatcher();

        return static::$server = $fly::getServer();
    }


}

