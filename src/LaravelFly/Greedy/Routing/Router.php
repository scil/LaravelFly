<?php

namespace LaravelFly\Greedy\Routing;

use Illuminate\Routing\Route as BaseRoute;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Container\Container;

class Router extends \Illuminate\Routing\Router
{

    /**
     * Override
     */
    protected function newRoute($methods, $uri, $action)
    {
        if ($this->container->isBooted()) {
            // routes creaed during request are not compiled auto. They are compiled when match
            return (new BaseRoute($methods, $uri, $action))
                ->setRouter($this)
                ->setContainer($this->container);

        } else {
            // before any request, routes are compiled auto.
            return parent::newRoute($methods, $uri, $action)

        }
    }
}