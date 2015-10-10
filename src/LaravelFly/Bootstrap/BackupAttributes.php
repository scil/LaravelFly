<?php
/**
 * Created by PhpStorm.
 * User: ivy
 * Date: 2015/8/6
 * Time: 2:50
 */

namespace LaravelFly\Bootstrap;

use LaravelFly\Application;

class BackupAttributes
{

    public function bootstrap(Application $app)
    {

        $app->backUpOnWorker();

    }
}