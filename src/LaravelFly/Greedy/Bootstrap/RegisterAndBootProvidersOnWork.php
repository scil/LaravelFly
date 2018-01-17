<?php

namespace LaravelFly\Greedy\Bootstrap;

use LaravelFly\Greedy\Application;

class RegisterAndBootProvidersOnWork
{
    public function bootstrap(Application $app)
    {

        $appConfig = $app['config'];
        $providers = $appConfig['laravelfly.providers_in_worker'];

        $providers = $appConfig['laravelfly.providers_on_worker'];
        $app->setProvidersToBootOnWorker($providers);

        $app->registerConfiguredProvidersBootOnWorker();
        $app->bootOnWorker();


        $needBackup = [];
        foreach (array_values($providers) as $singles) {
            foreach ($singles as $name => $config) {
                if (is_array($config)) {

                   $app->make($name);

                    if ($config) {
                        $needBackup[$name] = $config;
                    }

                }
            }
        }
        $app->addNeedBackupServiceAttributes($needBackup);
    }
}
