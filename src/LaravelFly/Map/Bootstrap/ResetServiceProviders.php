<?php


namespace LaravelFly\Map\Bootstrap;

use LaravelFly\Map\Application;

class ResetServiceProviders
{
    public function bootstrap(Application $app)
    {
        $app->resetServiceProviders();
    }
}
