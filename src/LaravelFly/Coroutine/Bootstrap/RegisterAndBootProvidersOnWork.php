<?php

namespace LaravelFly\Coroutine\Bootstrap;

use LaravelFly\Coroutine\Application;

class RegisterAndBootProvidersOnWork
{
    public function bootstrap(Application $app)
    {
        $providers = $app['config']['laravelfly.providers_on_worker'];

        $app->registerConfiguredProvidersBootOnWorker();
        $app->bootOnWorker();

        foreach (array_values($providers) as $singles) {
            foreach ($singles as $name => $config) {
                if ($config) {
                    $app->make($name);
                }
            }
        }

    }
}
