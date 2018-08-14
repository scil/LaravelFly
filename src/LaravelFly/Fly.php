<?php

namespace LaravelFly;

use LaravelFly\Exception\LaravelFlyException;
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

        $class = LARAVELFLY_MODE === 'FpmLike' ? \LaravelFly\Server\FpmHttpServer::class : $options['server'];

        if(!is_subclass_of($class, \LaravelFly\Server\ServerInterface::class))
            throw new LaravelFlyException("wrong server config 'server': ${options['server']} ");

        static::$server = new $class($dispatcher);

        static::$server->config($options);

        static::$server->createSwooleServer();

        return self::$server;
    }

    static protected function initEnv($options)
    {
        if ($options['early_laravel'] ?? false)
            require_once __DIR__ . '/../constants-1.php';
        else
            require_once __DIR__ . '/../constants.php';

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
