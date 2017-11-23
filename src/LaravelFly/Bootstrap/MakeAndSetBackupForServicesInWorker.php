<?php

namespace LaravelFly\Bootstrap;

use LaravelFly\Application;

class MakeAndSetBackupForServicesInWorker
{

    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');

        $needBackup = [];

        foreach ($appConfig['laravelfly.BaseServices'] as $name => $config) {
            if ($config) {
                $needBackup[$name] = $config;
            }
        }

        if (LARAVELFLY_GREEDY) {
            foreach ($appConfig['laravelfly.services_to_make_in_worker'] as $name => $config) {

                if (is_array($config)) {

                    $app->make($name);

                    if ($config) {
                        $needBackup[$name] = $config;
                    }

                }
            }

        }

        $app->setNeedBackupServiceAttributes($needBackup);


    }
}