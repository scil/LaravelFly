<?php

namespace LaravelFly\Bootstrap;

use LaravelFly\Application;

class SetProvidersInRequest
{

    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');

        if (!empty($appConfig['laravelfly.providers_in_request'])) {

            $ps = array_intersect($appConfig['app.providers'], $appConfig['laravelfly.providers_in_request']);

            if ($ps) {

                $app->prepareForProvidersInRequest($ps);

                $appConfig['app.providers'] = array_diff($appConfig['app.providers'], $ps);

                if ($appConfig['app.debug']) {
                    echo  '[NOTICE] Providers in request ( they are removed from config["app.providers"] )',
                    implode(', ',$ps),'.From:',__CLASS__, PHP_EOL;
                }
            }

        }

    }
}