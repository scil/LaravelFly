<?php

namespace LaravelFly;

class LaravelFly
{
    /**
     * @var \LaravelFly\Server\ServerInterface
     */
    protected static $server;

    /**
     * @var LaravelFly
     */
    protected static $instance;

    public static function getInstance($options = null)
    {
        if (!self::$instance) {
            try {
                self::$instance = new static();
                $class = LARAVELFLY_MODE == 'FpmLike' ? \LaravelFly\Server\FpmHttpServer::class : $options['server'];
                unset($options['server']);
                self::$server = new $class($options);
            } catch (\Throwable $e) {
                die('[FAILED] ' . $e->getMessage() . PHP_EOL);
            }
        }
        return self::$instance;
    }

    static function getServer()
    {
        if (!self::$instance) {
            throw new \Exception('LaravelFly is not ready');
        }
        return  self::$server;
    }

    function start()
    {
        static::getServer()->start();
    }

}
