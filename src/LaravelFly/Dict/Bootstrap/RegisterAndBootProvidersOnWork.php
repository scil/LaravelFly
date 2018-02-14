<?php

namespace LaravelFly\Dict\Bootstrap;

use LaravelFly\Dict\Application;

class RegisterAndBootProvidersOnWork
{
    public function bootstrap(Application $app)
    {

        $app->registerConfiguredProvidersBootOnWorker();
        $app->bootOnWorker();
        $app->makeCFServices();

    }
}
