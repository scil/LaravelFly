<?php

namespace LaravelFly\Normal\Bootstrap;

use LaravelFly\Normal\Application;

class BackupConfigs
{

    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');



        $needBackup = [];
        foreach ($appConfig['laravelfly.config_changed_in_requests'] as $config) {
            if (isset($appConfig[$config])) {
                $needBackup[$config] = $appConfig[$config];
            }

        }
        $app->setNeedBackupConfigs($needBackup);

    }
}