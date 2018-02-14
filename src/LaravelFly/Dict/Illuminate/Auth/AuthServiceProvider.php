<?php

namespace LaravelFly\Dict\Illuminate\Auth;


class AuthServiceProvider extends \Illuminate\Auth\AuthServiceProvider
{
    static public function coroutineFriendlyServices()
    {
        return ['auth'];
    }

    protected function registerAuthenticator()
    {
        $this->app->singleton('auth', function ($app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            $app['auth.loaded'] = true;

            // hack
            return new \LaravelFly\Dict\Illuminate\Auth\AuthManager($app);
        });

        $this->app->singleton('auth.driver', function ($app) {
            return $app['auth']->guard();
        });
    }

}
