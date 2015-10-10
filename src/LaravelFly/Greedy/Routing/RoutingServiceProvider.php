<?php
/**
 * Created by PhpStorm.
 * User: ivy
 * Date: 2015/9/9
 * Time: 1:30
 *
 * only for compiling all routes made before any request
 */

namespace LaravelFly\Greedy\Routing;


class RoutingServiceProvider extends \Illuminate\Routing\RoutingServiceProvider
{

    /**
     * Override
     */
    protected function registerRouter()
    {
        $this->app['router'] = $this->app->share(function ($app) {
            return new Router($app['events'], $app);
        });
        $this->app->booted(function(){
           app('router')->appBooted();
        });
    }
}