<?php

namespace LaravelFly\Normal\Bootstrap;

use LaravelFly\Normal\Application;

class CleanProviders
{
    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');

        $ps = $appConfig['laravelfly.providers_in_request'];

        $appConfig['app.providers'] = array_diff(
            $appConfig['app.providers'],
            $ps,
            $appConfig->get('laravelfly.providers_ignore')
        );

        if ($ps) {
            $app->makeManifestForProvidersInRequest($ps);
        }

    }
}
