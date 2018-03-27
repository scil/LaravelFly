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

    static $flyMap = [
        'Container.php' => '/vendor/laravel/framework/src/Illuminate/Container/Container.php',
        'Application.php' => '/vendor/laravel/framework/src/Illuminate/Foundation/Application.php',
        'ServiceProvider.php' => '/vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php',
        'FileViewFinder.php' => '/vendor/laravel/framework/src/Illuminate/View/FileViewFinder.php',
        'Router.php' => '/vendor/laravel/framework/src/Illuminate/Routing/Router.php',
        'ViewConcerns/ManagesComponents.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesComponents.php',
        'ViewConcerns/ManagesLayouts.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLayouts.php',
        'ViewConcerns/ManagesLoops.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesLoops.php',
        'ViewConcerns/ManagesStacks.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesStacks.php',
        'ViewConcerns/ManagesTranslations.php' => '/vendor/laravel/framework/src/Illuminate/View/Concerns/ManagesTranslations.php',
        'Facade.php' => '/vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php',

        //blackhole
        'Collection.php' => '/vendor/laravel/framework/src/Illuminate/Support/Collection.php',
        'Controller.php' => '/vendor/laravel/framework/src/Illuminate/Routing/Controller.php',
        'Relation.php' => '/vendor/laravel/framework/src/Illuminate/Database/Eloquent/Relations/Relation.php',
    ];

    /**
     * @param array $options
     * @param EventDispatcher $dispatcher
     */
    static function init($options, EventDispatcher $dispatcher = null): self
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

        self::$server->create();

        return self::$instance;
    }

    static protected function initEnv()
    {

        require_once __DIR__ . '/../functions.php';

        if (class_exists('NunoMaduro\Collision\Provider'))
            (new \NunoMaduro\Collision\Provider)->register();

        if (LARAVELFLY_MODE === 'Map') {
            foreach (static::$flyMap as $f => $offical) {
                require __DIR__ . "/../fly/" . $f;
            }
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
