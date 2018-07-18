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
     * @return \LaravelFly\Server\Common
     */
    public static function getCommonServer(): \LaravelFly\Server\Common
    {
        if (static::$commonServer) return static::$commonServer;

        return static::$commonServer = new \LaravelFly\Server\Common();
    }

}