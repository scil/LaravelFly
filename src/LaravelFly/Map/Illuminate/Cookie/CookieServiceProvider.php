<?php

namespace LaravelFly\Map\Illuminate\Cookie;

use Illuminate\Support\ServiceProvider;

class CookieServiceProvider extends ServiceProvider
{
    static public function coroutineFriendlyServices()
    {
        /**
         * CookieJar's path, domain, secure and sameSite  are not rewriten to be a full COROUTINE-FRIENDLY SERVICE.
         * so this provider requires there values always be same in all requests.
         */

        return ['cookie'];
    }
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cookie', function ($app) {
            $config = $app->make('config')->get('session');

            return (new CookieJar)->setDefaultPathAndDomain(
                $config['path'], $config['domain'], $config['secure'], $config['same_site'] ?? null
            );
        });
    }
}
