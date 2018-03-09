<?php

namespace LaravelFly\Hash\Bootstrap;

use LaravelFly\Hash\Application;

class RegisterAndBootProvidersOnWork
{
    public function bootstrap(Application $app)
    {

        $app->registerConfiguredProvidersBootOnWorker();
        $app->bootOnWorker();
        $app->makeCFServices();

    }
}
