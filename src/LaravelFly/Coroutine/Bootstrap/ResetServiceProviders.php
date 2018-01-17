<?php


namespace LaravelFly\Coroutine\Bootstrap;

use LaravelFly\Coroutine\Application;

class ResetServiceProviders
{
    public function bootstrap(Application $app)
    {
        $app->resetServiceProviders();
    }
}
