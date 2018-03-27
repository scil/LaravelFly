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
    static function init($options, EventDispatcher $dispatcher = null):self
    {
        if (self::$instance) return self::$instance;

        static::initEnv();

        if (null === static::$dispatcher) {
            if (null === $dispatcher)
                $dispatcher = new EventDispatcher();
            self::$dispatcher = $dispatcher;
        }

        printf("[INFO] server events ready\n");

        self::$instance = new static();

        $dispatcher = static::$dispatcher;

        $class = LARAVELFLY_MODE === 'FpmLike' ? \LaravelFly\Server\FpmHttpServer::class : $options['server'];

        self::$server = new $class($dispatcher);

        self::$server->config($options);

        //self::$server->loadCachedCompileFile();

        self::$server->create();

        return self::$instance;
    }

    static protected function initEnv()
    {

        require_once __DIR__. '/../functions.php';

        if (class_exists('NunoMaduro\Collision\Provider'))
            (new \NunoMaduro\Collision\Provider)->register();

        if (LARAVELFLY_MODE === 'Map') {
            require __DIR__ . "/../fly/Container.php";
            require __DIR__ . "/../fly/Application.php";
            require __DIR__ . "/../fly/ServiceProvider.php";
            require __DIR__ . "/../fly/Router.php";
            require __DIR__ . "/../fly/ViewConcerns/ManagesComponents.php";
            require __DIR__ . "/../fly/ViewConcerns/ManagesLayouts.php";
            require __DIR__ . "/../fly/ViewConcerns/ManagesLoops.php";
            require __DIR__ . "/../fly/ViewConcerns/ManagesStacks.php";
            require __DIR__ . "/../fly/ViewConcerns/ManagesTranslations.php";
            require __DIR__ . "/../fly/Facade.php";

            //blackhole
            require __DIR__ . "/../fly/Controller.php";
            require __DIR__ . "/../fly/Relation.php";
            require __DIR__ . "/../fly/Collection.php";

        }

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
        if (null === self::$dispatcher) {
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
