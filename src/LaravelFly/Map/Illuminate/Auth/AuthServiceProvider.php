<?php

namespace LaravelFly\Map\Illuminate\Auth;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class AuthServiceProvider extends \Illuminate\Auth\AuthServiceProvider
{
    static public function coroutineFriendlyServices():array
    {
        return [

            'auth',

            /**
             * do not resolve it on work, because it has props like afterCallbacks which may cause memory leak
             * it's official implement is Illuminate\Auth\Access\Gate
             */
            // GateContract::class
        ];
    }

    protected function registerAuthenticator()
    {
        $this->app->singleton('auth', function ($app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            $app['auth.loaded'] = true;

            // hack
            return new \LaravelFly\Map\Illuminate\Auth\AuthManager($app);
        });

        $this->app->singleton('auth.driver', function ($app) {
            return $app['auth']->guard();
        });
    }

}
