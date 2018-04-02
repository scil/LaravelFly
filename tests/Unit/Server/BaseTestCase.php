<?php

namespace LaravelFly\Tests\Unit\Server;

use LaravelFly\Tests\BaseTestCase as Base;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class BaseTestCase extends Base
{
    /**
     * @var \LaravelFly\Server\Common;
     */
    static $commonServer;

    static $default = [];

    /**
     * This method is called before each test.
     */
    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$commonServer = new \LaravelFly\Server\Common();

        $d = new \ReflectionProperty(static::$commonServer, 'defaultOptions');
        $d->setAccessible(true);
        static::$default = $d->getValue(static::$commonServer);
    }

    function setSwooleServer($options):\swoole_http_server
    {
        $swoole= new \swoole_http_server($options['listen_ip'], $options['listen_port']);
        $swoole->set($options);

        $s = new \ReflectionProperty(static::$commonServer, 'swoole');
        $s->setAccessible(true);
        $s->setValue(static::$commonServer,$swoole);

        return $swoole;
    }

    function resetConfigAndDispatcher()
    {
        $c = new \ReflectionProperty(static::$commonServer, 'options');
        $c->setAccessible(true);
        $c->setValue(static::$commonServer,[]);

        $d = new \ReflectionProperty(static::$commonServer, 'dispatcher');
        $d->setAccessible(true);
        $d->setValue(static::$commonServer,new EventDispatcher());

    }
}