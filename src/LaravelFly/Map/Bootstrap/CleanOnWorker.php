<?php

namespace LaravelFly\Map\Bootstrap;

use LaravelFly\Map\Application;
use Illuminate\Support\Facades\Facade;

class CleanOnWorker
{
    public function bootstrap(Application $app)
    {
        $app->resetServiceProviders();

        Facade::clearResolvedInstance('request');

        //'url' has made? when? \Illuminate\Routing\RoutingServiceProvider
        Facade::clearResolvedInstance('url');
    }
}
