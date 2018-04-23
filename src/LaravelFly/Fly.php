<?php

namespace LaravelFly;

use Symfony\Component\EventDispatcher\EventDispatcher;

class Fly
{

    /**
     * @var \LaravelFly\Server\ServerInterface | \LaravelFly\Server\HttpServer
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
    static function init(array $options, EventDispatcher $dispatcher = null): self
    {
        if (self::$instance) return self::$instance;

        static::initEnv();

        if (null === $dispatcher)
            $dispatcher = new EventDispatcher();

        echo "[INFO] server dispatcher created\n";

        static::$instance = new static();

        $class = LARAVELFLY_MODE === 'FpmLike' ? \LaravelFly\Server\FpmHttpServer::class : $options['server'];

        static::$server = new $class($dispatcher);

        static::$server->config($options);

        static::$server->createSwooleServer();

        return self::$instance;
    }

    static protected function initEnv()
    {

        require_once __DIR__ . '/../functions.php';

        if (class_exists('NunoMaduro\Collision\Provider'))
            (new \NunoMaduro\Collision\Provider)->register();


    }

    public static function getInstance($options = null): self
    {

        if (!self::$instance) {
            static::init($options);
        }
        return self::$instance;
    }

    function start()
    {
        static::$server->start();
    }


    function getDispatcher()
    {
        return static::$server->getDispatcher();
    }

    function getServer()
    {
        return static::$server;
    }

}
