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

                $app->prepareIfProvidersInRequest($ps);

                $appConfig['app.providers'] = array_diff($appConfig['app.providers'], $ps);

                if ($appConfig['app.debug']) {
                    echo PHP_EOL, 'Providers in request ( they are removed from config["app.providers"] )', PHP_EOL,__CLASS__, PHP_EOL ;
                    var_dump($ps);
                }
            }

        }

    }
}