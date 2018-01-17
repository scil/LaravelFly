<?php

namespace LaravelFly\Coroutine\Bootstrap;

use LaravelFly\Coroutine\Application;

class RegisterAndBootProvidersOnWork
{
    public function bootstrap(Application $app)
    {

        $app->registerConfiguredProvidersBootOnWorker();
        $app->bootOnWorker();

        $providers = $app['config']['laravelfly.providers_on_worker'];
        foreach (array_values($providers) as $singles) {
            foreach ($singles as $name => $config) {
                if ($config) {
                    $app->make($name);
                }
            }
        }

    }
}
