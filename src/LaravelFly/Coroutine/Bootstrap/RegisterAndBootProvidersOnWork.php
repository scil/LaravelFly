<?php

namespace LaravelFly\Coroutine\Bootstrap;

use LaravelFly\Coroutine\Application;

class RegisterAndBootProvidersOnWork
{
    public function bootstrap(Application $app)
    {

            $app->registerConfiguredProviders();
            $app->bootOnWorker();
    }
}
