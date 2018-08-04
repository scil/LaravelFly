<?php

namespace LaravelFly\Backup\Bootstrap;

use LaravelFly\Backup\Application;

class BackupConfigs
{

    public function bootstrap(Application $app)
    {
        if (empty(LARAVELFLY_SERVICES['config']))
            $app->setBackupedConfig();

    }
}