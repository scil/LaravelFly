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

        $services = array_unique(array_merge(['url', 'request'], $services));

        foreach ($services as $service) {
            Facade::clearResolvedInstance($service);
        }

    }
}
