<?php

namespace LaravelFly\Bootstrap;

use LaravelFly\Application;

class BackupAttributes
{

    public function bootstrap(Application $app)
    {

        $app->backUpOnWorker();

    }
}