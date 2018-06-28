<?php

namespace LaravelFly\Simple\Bootstrap;

use LaravelFly\Simple\Application;

class BackupConfigs
{

    public function bootstrap(Application $app)
    {

        if (defined('LARAVELFLY_SERVICES') && !LARAVELFLY_SERVICES['config'])
            $app->setBackupedConfig();


    }
}