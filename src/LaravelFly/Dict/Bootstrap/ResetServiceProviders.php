<?php


namespace LaravelFly\Dict\Bootstrap;

use LaravelFly\Dict\Application;

class ResetServiceProviders
{
    public function bootstrap(Application $app)
    {
        $app->resetServiceProviders();
    }
}
