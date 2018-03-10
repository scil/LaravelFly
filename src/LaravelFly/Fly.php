<?php

namespace LaravelFly;

use LaravelFly\Exception\LaravelFlyException as Exception;
use LaravelFly\Exception\LaravelFlyException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Fly
{
    /**
     * @var EventDispatcher
     */
    protected static $dispatcher;

    /**
     * @var \LaravelFly\Server\ServerInterface
     */
    protected static $server;

    /**
     * @var Fly
     */
    protected static $instance;

    /**
     * @param array $options
     * @param EventDispatcher $dispatcher
     */
    static function init($options, EventDispatcher $dispatcher = null)
    {
        if (self::$instance) return;

        if (null === static::$dispatcher) {
            if (null === $dispatcher)
                static::setDispatcher(new EventDispatcher());
            self::$dispatcher = $dispatcher;
        }


        printf("[INFO] server events ready\n");

        self::$instance = new static();

        $dispatcher = static::$dispatcher;

        $class = LARAVELFLY_MODE == 'FpmLike' ? \LaravelFly\Server\FpmHttpServer::class : $options['server'];

        self::$server = new $class($dispatcher);

        self::$server->config($options);

        self::$server->create();

    }

    function start()
    {
        static::getServer()->start();
    }

    public static function getInstance($options = null)
    {

        if (!self::$instance) {
            static::init($options);
        }
        return self::$instance;
    }

    static function getDispatcher()
    {
        if (null=== self::$dispatcher) {
            static::$dispatcher = new EventDispatcher();
        }
        return self::$dispatcher;
    }

    static function getServer()
    {
        if (!self::$instance) {
            throw new Exception('LaravelFly is not ready');
        }
        return self::$server;
    }

}
