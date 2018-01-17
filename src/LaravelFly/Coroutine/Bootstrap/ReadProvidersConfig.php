<?php

namespace LaravelFly\Coroutine\Bootstrap;

use LaravelFly\Coroutine\Application;

class ReadProvidersConfig
{

    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');

        $psInRequest = $appConfig['laravelfly.providers_in_request'];

        $appConfig['app.providers'] = array_diff(
            $appConfig['app.providers'],
            $psInRequest,
            $appConfig['laravelfly.providers_ignore']
        );

        $providers = array_keys($appConfig['laravelfly.providers_on_worker']);
        $app->setProvidersToBootOnWorker($providers);

        if ($psInRequest) {
            $app->makeManifestForProvidersInRequest($psInRequest);
        }

    }
}