<?php

namespace LaravelFly\Coroutine\Bootstrap;

use LaravelFly\Coroutine\Application;

class ReadProvidersConfig
{

    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');

        $psInRequest = $appConfig['laravelfly.providers_in_request'];

        $worker_providers = $app['config']['laravelfly.providers_on_worker'];
        $CFServices = [];
        $replaced = [];
        foreach (array_values($worker_providers) as $singles) {
            foreach ($singles as $name => $config) {
                if ($config === true) {
                    $CFServices[] = $name;
                } elseif ($config === 'replaced') {
                    $replaced[] = $name;
                }
            }
        }

        $appConfig['app.providers'] = array_diff(
            $appConfig['app.providers'],
            $replaced,
            $psInRequest,
            $appConfig['laravelfly.providers_ignore']
        );

        $app->makeManifestForProvidersInRequest($psInRequest);

        $app->setProvidersToBootOnWorker(array_keys($worker_providers));

        $app->setCFServices($CFServices);

    }
}