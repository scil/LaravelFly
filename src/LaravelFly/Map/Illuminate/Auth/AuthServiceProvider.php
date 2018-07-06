<?php

namespace LaravelFly\Map\Illuminate\Auth;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;

class AuthServiceProvider extends \Illuminate\Auth\AuthServiceProvider
{
    static public function coroutineFriendlyServices():array
    {
        return [
            'auth',
             GateContract::class
        ];
    }

    protected function registerAccessGate()
    {
        $this->app->singleton(GateContract::class, function ($app) {
            return new Gate($app, function () use ($app) {
                return call_user_func($app['auth']->userResolver());
            });
        });
    }

    protected function registerAuthenticator()
    {
        $this->app->singleton('auth', function ($app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            $app['auth.loaded'] = true;

            // hack
            $class = LARAVELFLY_SERVICES['auth']?
                \LaravelFly\Map\Illuminate\Auth\AuthManagerSame::class:
                \LaravelFly\Map\Illuminate\Auth\AuthManager ::class;
            return new $class($app);
        });

        $this->app->singleton('auth.driver', function ($app) {
            return $app['auth']->guard();
        });
    }

}
