<?php

namespace LaravelFly\Hash\IlluminateBase;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;

class EventServiceProvider extends ServiceProvider
{

    /**
     * Override
     */
    function register()
    {
        $this->app->singleton('events', function ($app) {
            return (new Dispatcher($app))->setQueueResolver(function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
    }
}