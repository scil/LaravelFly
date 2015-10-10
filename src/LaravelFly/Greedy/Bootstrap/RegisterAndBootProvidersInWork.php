<?php

namespace LaravelFly\Greedy\Bootstrap;

use LaravelFly\Greedy\Application;

class RegisterAndBootProvidersInWork
{
    public function bootstrap(Application $app)
    {
        $app->setProvidersToBootInWorker();
        $app->registerConfiguredProvidersBootInWorker();
        $app->bootOnWorker();
    }
}
