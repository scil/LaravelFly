<?php

namespace LaravelFly\Greedy\Routing;


class RoutingServiceProvider extends \Illuminate\Routing\RoutingServiceProvider
{

    /**
     * Override
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new Router($app['events'], $app);
        });
    }
}