<?php

namespace LaravelFly\Tests\Map\Unit\Server;

use LaravelFly\Tests\Map\MapTestCase as Base;
use Symfony\Component\EventDispatcher\EventDispatcher;




abstract class CommonServerTestCase extends Base
{

    /**
     * @var \LaravelFly\Server\Common;
     */
    static $commonServer;

    /**
     * create a server and get default server options
     */
    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::makeCommonServer();



    }

    /**
     * @return \LaravelFly\Server\Common
     */
    public static function getCommonServer(): \LaravelFly\Server\Common
    {
        return self::$commonServer;
    }

    static protected function makeCommonServer()
    {
        if (static::$commonServer) return static::$commonServer;


        static::$commonServer = new \LaravelFly\Server\Common();

        // get default server options
        $d = new \ReflectionProperty(static::$commonServer, 'defaultOptions');
        $d->setAccessible(true);
        $options = $d->getValue(static::$commonServer);
        $options['pre_include'] = false;
        $options['colorize'] = false;
        static::$default = $options;

        return static::$commonServer;
    }

}