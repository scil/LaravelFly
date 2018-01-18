<?php

namespace LaravelFly\Coroutine\Illuminate;

use Illuminate\Routing\Route as BaseRoute;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Container\Container;

class Router extends \Illuminate\Routing\Router
{
    /**
     * The IoC container instance.
     *
     * @var \LaravelFly\Coroutine\Application
     */
    protected $container;

    function __clone()
    {
        $this->container = Container::getInstance();
        $this->routes= $this->container['routes'];
    }
}