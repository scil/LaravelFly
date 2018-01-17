<?php

namespace LaravelFly\One\Bootstrap;

use LaravelFly\One\Application;

class BackupAttributes
{

    public function bootstrap(Application $app)
    {

        $app->backUpOnWorker();

    }
}