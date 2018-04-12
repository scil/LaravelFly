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
     * create a server and get default server options
     */
    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$commonServer = new \LaravelFly\Server\Common();

        // get default server options
        $d = new \ReflectionProperty(static::$commonServer, 'defaultOptions');
        $d->setAccessible(true);
        static::$default = $d->getValue(static::$commonServer);
    }

    /**
     * @return \LaravelFly\Server\Common
     */
    public static function getCommonServer(): \LaravelFly\Server\Common
    {
        return self::$commonServer;
    }

    /**
     * to create swoole server in phpunit, use this instead of server::setSwooleServer
     *
     * @param $options
     * @return \swoole_http_server
     * @throws \ReflectionException
     *
     * server::setSwooleServer may produce error:
     *  Fatal error: Swoole\Server::__construct(): eventLoop has already been created. unable to create swoole_server.
     */
    function setSwooleServer($options): \swoole_http_server
    {
        $options = array_merge(self::$default, $options);

        $swoole = new \swoole_http_server($options['listen_ip'], $options['listen_port']);
        $swoole->set($options);

        $s = new \ReflectionProperty(static::$commonServer, 'swoole');
        $s->setAccessible(true);
        $s->setValue(static::$commonServer, $swoole);


        $swoole->fly = static::$commonServer;
        static::$commonServer->setListeners();

        return $swoole;
    }

    function resetConfigAndResetDispatcher()
    {
        $c = new \ReflectionProperty(static::$commonServer, 'options');
        $c->setAccessible(true);
        $c->setValue(static::$commonServer, []);

        $d = new \ReflectionProperty(static::$commonServer, 'dispatcher');
        $d->setAccessible(true);
        $d->setValue(static::$commonServer, new EventDispatcher());

    }
}