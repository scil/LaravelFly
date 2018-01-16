<?php

namespace LaravelFly\Coroutine\Bootstrap;

use LaravelFly\Coroutine\Application;

class CleanProviders
{

    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');

        $ps = $appConfig['laravelfly.providers_in_request'];

        $appConfig['app.providers'] = array_diff(
            $appConfig['app.providers'],
            $ps,
            $appConfig['laravelfly.providers_ignore']
        );

        $providers = $appConfig['laravelfly.providers_on_worker'];
        $app->setProvidersToBootOnWorker($providers);

        if ($ps) {
            $app->makeManifestForProvidersInRequest($ps);
        }

    }
}