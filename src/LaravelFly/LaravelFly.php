<?php

namespace LaravelFly;

use LaravelFly\Exception\LaravelFlyException as Exception;
use LaravelFly\Exception\LaravelFlyException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class LaravelFly
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
     * @var LaravelFly
     */
    protected static $instance;

    /**
     * @param EventDispatcher $dispatcher
     */
    public static function setDispatcher(EventDispatcher $dispatcher)
    {
        if (self::$instance) {
            throw new LaravelFlyException(__CLASS__.' has inited');
        };

        self::$dispatcher = $dispatcher;
    }

    static function init($options = null)
    {
        if (self::$instance) return;

        self::$instance = new static();

        if (null === static::$dispatcher) {
            static::$dispatcher = new EventDispatcher();
        }

        $dispatcher = static::$dispatcher;

        $class = LARAVELFLY_MODE == 'FpmLike' ? \LaravelFly\Server\FpmHttpServer::class : $options['server'];

        self::$server = new $class($dispatcher);

        self::$server->config($options);

        self::$server->create();

    }

    public static function getInstance($options = null)
    {

        if (!self::$instance) {
            static::init($options);
        }
        return self::$instance;
    }


    static function getServer()
    {
        if (!self::$instance) {
            throw new Exception('LaravelFly is not ready');
        }
        return self::$server;
    }

    function start()
    {
        static::getServer()->start();
    }


}
