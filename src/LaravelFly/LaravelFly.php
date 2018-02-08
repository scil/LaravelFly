<?php
namespace LaravelFly;

class LaravelFly
{
    /**
     * @var \LaravelFly\Server\ServerInterface
     */
    protected $server;

    /**
     * @var \LaravelFly
     */
    protected static $instance;

    public static function getInstance($options)
    {
        if (!self::$instance) {
            try {
                self::$instance = new static($options);
            } catch (\Throwable $e) {
                die('[FAILED] ' . $e->getMessage() . PHP_EOL);
            }
        }
        return self::$instance;
    }
    function __construct(array $options)
    {
        $class = $options['server'];
        unset($options['server']);
        $this->server = new $class($options);
    }

    function start()
    {
        $this->server->start();
    }

}
