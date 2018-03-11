<?php

namespace LaravelFly\Map\Bootstrap;

use LaravelFly\Map\Application;

class RegisterAndBootProvidersOnWork
{
    public function bootstrap(Application $app)
    {

        $app->registerConfiguredProvidersBootOnWorker();
        $app->bootOnWorker();
        $app->makeCFServices();

    }
}
