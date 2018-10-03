<?php

namespace LaravelFly\Map\Bootstrap;

use LaravelFly\Map\Application;
use Illuminate\Support\Facades\Facade;

class CleanOnWorker
{
    public function bootstrap(Application $app)
    {
        $app->resetServiceProviders();

        $services = $app->make('config')->get('laravelfly.clean_Facade_on_work', []);

        foreach (array_flatten($services) as $service) {
            $service && Facade::clearResolvedInstance($service);
        }

    }
}
