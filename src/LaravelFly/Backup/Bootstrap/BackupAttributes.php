<?php

namespace LaravelFly\Backup\Bootstrap;

use LaravelFly\Backup\Application;

class BackupAttributes
{

    public function bootstrap(Application $app)
    {

        $app->backUpOnWorker();

    }
}