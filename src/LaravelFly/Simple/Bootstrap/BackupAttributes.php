<?php

namespace LaravelFly\Simple\Bootstrap;

use LaravelFly\Simple\Application;

class BackupAttributes
{

    public function bootstrap(Application $app)
    {

        $app->backUpOnWorker();

    }
}