<?php
/**
 * Created by PhpStorm.
 * User: ivy
 * Date: 2015/8/6
 * Time: 2:50
 */

namespace LaravelFly\Bootstrap;

use LaravelFly\Application;

class BackupConfigs
{

    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');



        $needBackup = [];
        foreach ($appConfig['laravelfly.config_need_backup'] as $config) {
            if (isset($appConfig[$config])) {
                $needBackup[$config] = $appConfig[$config];
            }

        }
        $app->setNeedBackupConfigs($needBackup);

    }
}