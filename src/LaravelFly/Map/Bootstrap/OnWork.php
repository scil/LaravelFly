<?php

namespace LaravelFly\Map\Bootstrap;

use LaravelFly\Map\Application;

class OnWork
{
    public function bootstrap(Application $app)
    {

        $app->registerWorkerProviders();
        $app->bootOnWorker();
        $app->makeCFServices();

        $config = $app->make('config');

        if (LARAVELFLY_SERVICES['kernel']) {

            $app->setSingletonMiddlewares(
                $config->get('laravelfly.singleton_middlewares', [])
            );
        }

        $router = $app->make('router');

        $router->setSingletonMiddlewares(
            $config->get('laravelfly.singleton_route_middlewares', [])
        );

        if (LARAVELFLY_SERVICES['routes'] && LARAVELFLY_SERVICES['kernel']) {
            $router->enableMiddlewareAlwaysStable();
        }
    }
}
