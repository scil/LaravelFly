<?php

namespace LaravelFly;

use LaravelFly\Server\ServerInterface;
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
    static function init(array $options, EventDispatcher $dispatcher = null): ServerInterface
    {
        if (self::$server) return self::$server;

        static::initEnv($options);

        if (!in_array(LARAVELFLY_MODE, ['Map', 'Backup', 'FpmLike']))
            die("const LARAVELFLY_MODE must be one of ['Map', 'Backup', 'FpmLike']\n");

        $class = LARAVELFLY_MODE === 'FpmLike' ? \LaravelFly\Server\FpmHttpServer::class : $options['server'];

        if (!is_subclass_of($class, \LaravelFly\Server\ServerInterface::class))
            die("wrong server config 'server': ${options['server']} \n");

        static::$server = new $class($dispatcher);

        static::$server->config($options);

        static::$server->createSwooleServer();

        return self::$server;
    }

    static protected function initEnv($options)
    {

        require_once __DIR__ . '/../functions.php';

        if (class_exists('NunoMaduro\Collision\Provider'))
            (new \NunoMaduro\Collision\Provider)->register();

    }

    public static function getServer($options = null):ServerInterface
    {

        if (!self::$server) {
            static::init($options);
        }
        return self::$server;
    }

}
