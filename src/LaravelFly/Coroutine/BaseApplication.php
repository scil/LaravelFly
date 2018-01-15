<?php

namespace LaravelFly\Coroutine;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Filesystem\Filesystem;

use LaravelFly\Greedy\Routing\RoutingServiceProvider;
use LaravelFly\ProviderRepository;
use Illuminate\Contracts\Container\Container as ContainerContract;

class BaseApplication extends \LaravelFly\Application
{
    /**
     * @var array all application instances live now in current worker
     */
    protected static $self_instances = [];

    public static function getInstance()
    {
        $cID = \Swoole\Coroutine::getuid();
        if (empty(static::$self_instances[$cID])) {
            //todo
//            static::$self_instances[$cID] = new static;
        }
        return static::$self_instances[$cID];
    }

    public static function setInstance(ContainerContract $container = null)
    {
        return static::$self_instances[\Swoole\Coroutine::getuid()] = $container;
    }


}