<?php

namespace LaravelFly\Map\Bootstrap;

use LaravelFly\Map\Application;
use Illuminate\Support\Facades\Facade;

class CleanOnWorker
{
    public function bootstrap(Application $app)
    {
        $app->resetServiceProviders();

        $cloneServices = $app->cloneServices;

        // if( !LARAVELFLY_SERVICES['request']  ) $cloneServices = array_merge($cloneServices, ['request', 'url']);

        foreach ($cloneServices as $service) {

            // this is necessary for QUICK MAKE
            $app->forgetInstance($service);

            Facade::clearResolvedInstance($service);
        }

    }
}
