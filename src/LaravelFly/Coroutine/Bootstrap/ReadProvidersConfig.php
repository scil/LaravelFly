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
        $providersReplaced = [];
        $providersOnWork = [];
        foreach ($worker_providers as $provider => $providerConfig) {
            $replaced = false;

            foreach ($providerConfig as $CFS_name => $config) {
                if ($config === true) {
                    $CFServices[] = $CFS_name;
                } elseif ( !$replaced && $CFS_name == '_replaced_by' && class_exists($providerConfig['_replaced_by'])) {
                    $replaced = true;
                }
            }

            if ($replaced) {
                $providersOnWork[] = $providerConfig['_replaced_by'];
                $providersReplaced[] = $provider;
            } else {
                $providersOnWork[] = $provider;
            }
        }

        $appConfig['app.providers'] = array_diff(
            $appConfig['app.providers'],
            $providersReplaced,
            $psInRequest,
            $appConfig['laravelfly.providers_ignore']
        );

        $app->makeManifestForProvidersInRequest($psInRequest);

        $app->setProvidersToBootOnWorker($providersOnWork);

        $app->setCFServices($CFServices);

    }
}