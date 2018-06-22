<?php

namespace LaravelFly\Simple\Bootstrap;

use LaravelFly\Simple\Application;

class SetBackupForBaseServices
{

    public function bootstrap(Application $app)
    {

        $appConfig = $app->make('config');

        $needBackup = [];

        foreach ($appConfig['laravelfly.BaseServices'] ?: [] as $name => $config) {
            if ($config) {
                $needBackup[$name] = $config;
            }
        }

        if (defined('LARAVELFLY_SERVICES') && !(LARAVELFLY_SERVICES['kernel'] ?? true)) {

            $needBackup[\Illuminate\Contracts\Http\Kernel::class] = [
                'middleware',
            ];
        }

        $app->addNeedBackupServiceAttributes($needBackup);


    }
}