<?php

namespace LaravelFly\Coroutine\Bootstrap;

use LaravelFly\Coroutine\Application;

class RegisterAndBootProvidersOnWork
{
    public function bootstrap(Application $app)
    {
        $appConfig = $app['config'];
        $providers = $appConfig['laravelfly.providers_in_worker'];

        $app->registerConfiguredProvidersBootOnWorker($providers);
        $app->bootOnWorker();


        foreach (array_values($providers) as $singles) {
            foreach ($singles as $name => $config) {
                if (!$config) {
                    $app->make($name);
                }
            }
        }

    }
}
