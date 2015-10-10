<?php
/**
 * Created by PhpStorm.
 * User: ivy
 * Date: 2015/9/9
 * Time: 1:30
 *
 * only for compiled all routes which are made before request
 */

namespace LaravelFly\Routing;

use \Illuminate\Routing\Redirector;

class RoutingServiceProvider extends \Illuminate\Routing\RoutingServiceProvider
{

    /**
     * Override
     */
    protected function registerRedirector()
    {
        $this->app->singleton('redirect',function($app){
//        $this->app['redirect'] = $this->app->share(function ($app) {
            $redirector = new Redirector($app['url']);

            // If the session is set on the application instance, we'll inject it into
            // the redirector instance. This allows the redirect responses to allow
            // for the quite convenient "with" methods that flash to the session.
            if (isset($app['session.store'])) {
                $redirector->setSession($app['session.store']);
            }

            return $redirector;
        });
    }
}