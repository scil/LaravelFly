<?php

namespace LaravelFly\Normal\Bootstrap;

use LaravelFly\Normal\Application;

class SetProvidersInRequest
{

    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');

        if ($ps = $appConfig['laravelfly.providers_in_request']) {

            $app->makeManifestForProvidersInRequest($ps);

            $appConfig['app.providers'] = array_diff(
                $appConfig['app.providers'],
                $ps,
                $appConfig->get('laravelfly.providers_ignore')
                );

            if ($appConfig['app.debug']) {
                echo '[NOTICE] Providers in request ( they are removed from config["app.providers"] )',
                implode(', ', $ps), '.From:', __CLASS__, PHP_EOL;
            }

        }

    }
}