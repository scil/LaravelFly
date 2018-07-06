<?php

namespace LaravelFly\Map\Illuminate\Auth;


use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Auth\Factory as FactoryContract;

class AuthManager extends AuthManagerSame
{
    use \LaravelFly\Map\Util\Dict;

    // todo needed really?
    protected static $normalAttriForObj = ['userResolver' => null];

    protected static $arrayAttriForObj = [
        'guards',
        // plus
        'customCreators'
    ];


    public function __construct($app)
    {
       parent::__construct($app);

    }

    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Auth guard [{$name}] is not defined.");
        }

        if (isset(static::$corDict[\Swoole\Coroutine::getuid()]['customCreators'][$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new InvalidArgumentException("Auth driver [{$config['driver']}] for guard [{$name}] is not defined.");
    }

    protected function callCustomCreator($name, array $config)
    {
        return static::$corDict[\Swoole\Coroutine::getuid()]['customCreators'][$config['driver']]($this->app, $name, $config);
    }
    public function extend($driver, Closure $callback)
    {
        static::$corDict[\Swoole\Coroutine::getuid()]['customCreators'][$driver] = $callback;

        return $this;
    }
}

