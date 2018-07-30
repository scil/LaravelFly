<?php

namespace LaravelFly\Map\Bootstrap;

use LaravelFly\Map\Application;

class ProvidersAndServicesOnWork
{
    public function bootstrap(Application $app)
    {

        $app->registerConfiguredProvidersBootOnWorker();
        $app->bootOnWorker();
        $app->makeCFServices();

        $app->make('router')::setSingletonMiddlewares(
            $app->make('config')->get('laravelfly.singleton_route_middlewares', [])
        );
    }
}
