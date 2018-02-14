<?php

namespace LaravelFly\Greedy\Bootstrap;

use LaravelFly\Greedy\Application;

class ResetServiceProviders
{
    public function bootstrap(Application $app)
    {
        $app->resetServiceProviders();
    }
}
