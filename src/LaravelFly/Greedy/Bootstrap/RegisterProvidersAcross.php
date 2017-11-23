<?php

namespace LaravelFly\Greedy\Bootstrap;

use LaravelFly\Greedy\Application;

class RegisterProvidersAcross
{
    public function bootstrap(Application $app)
    {
        $app->registerProvidersAcross();
    }
}
