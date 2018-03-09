<?php


namespace LaravelFly\Hash\Bootstrap;

use LaravelFly\Hash\Application;

class ResetServiceProviders
{
    public function bootstrap(Application $app)
    {
        $app->resetServiceProviders();
    }
}
