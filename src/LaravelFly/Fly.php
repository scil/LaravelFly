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
     * @param array $options
     * @param EventDispatcher $dispatcher
     */
    static function init(array $options, EventDispatcher $dispatcher = null)
    {
        if (self::$server) return self::$server;

        static::initEnv($options);

        if (null === $dispatcher)
            $dispatcher = new EventDispatcher();

        $class = LARAVELFLY_MODE === 'FpmLike' ? \LaravelFly\Server\FpmHttpServer::class : $options['server'];

        static::$server = new $class($dispatcher);

        static::$server->config($options);

        static::$server->createSwooleServer();

        return self::$server;
    }

    static protected function initEnv($options)
    {
        if ($options['early_laravel'] ?? false)
            define('WORKER_COROUTINE_ID', -1);

        require_once __DIR__ . '/../functions.php';

        if (class_exists('NunoMaduro\Collision\Provider'))
            (new \NunoMaduro\Collision\Provider)->register();


    }

    public static function getServer($options = null)
    {

        if (!self::$server) {
            static::init($options);
        }
        return self::$server;
    }

}
