<?php

namespace LaravelFly\Tests\Map\Unit\Server;

use LaravelFly\Tests\Map\MapTestCase as Base;
use Symfony\Component\EventDispatcher\EventDispatcher;


abstract class CommonServerTestCase extends Base
{

    /**
     * @var \LaravelFly\Server\Common;
     */
    static $commonServerNoSwoole;

    /**
     * @return \LaravelFly\Server\Common
     */
    public static function getCommonServerNoSwoole(): \LaravelFly\Server\Common
    {
        if (static::$commonServerNoSwoole) return static::$commonServerNoSwoole;

        return static::$commonServerNoSwoole = new \LaravelFly\Server\Common();
    }

    function resetServerConfigAndDispatcher($server = null)
    {
        $server = $server ?: static::getCommonServerNoSwoole();
        $c = new \ReflectionProperty($server, 'options');
        $c->setAccessible(true);
        $c->setValue($server, []);

        $d = new \ReflectionProperty($server, 'dispatcher');
        $d->setAccessible(true);
        $d->setValue($server, new EventDispatcher());

    }

}