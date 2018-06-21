<?php

namespace LaravelFly\Simple\Bootstrap;

use LaravelFly\Simple\Application;

class BackupConfigs
{

    public function bootstrap(Application $app)
    {

        if (!(LARAVELFLY_SERVICES['config'] ?? true)) {

            $app->setBackupedConfig();
        }

    }
}