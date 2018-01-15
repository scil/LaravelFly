<?php

namespace LaravelFly\Normal\Bootstrap;

use LaravelFly\Normal\Application;

class BackupAttributes
{

    public function bootstrap(Application $app)
    {

        $app->backUpOnWorker();

    }
}