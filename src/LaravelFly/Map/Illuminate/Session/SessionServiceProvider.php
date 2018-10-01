<?php

namespace LaravelFly\Map\Illuminate\Session;


use LaravelFly\Fly;

class SessionServiceProvider extends \Illuminate\Session\SessionServiceProvider
{
    static public function coroutineFriendlyServices(): array
    {
        return [
            'session',
            'session.store',
            \Illuminate\Session\Middleware\StartSession::class
        ];
    }

    public function register()
    {
        $this->registerSessionManager();

        $this->registerSessionDriver();

        $this->app->singleton(\Illuminate\Session\Middleware\StartSession::class, function ($app) {
            // hack
            return new StartSession($app->make('session'));
        });

    }

    protected function registerSessionManager()
    {

        $this->app->singleton('session', function ($app) {
            // hack
            $m = new SessionManager($app);

            // hack
            if (Fly::getServer()->getConfig('swoole_session_back')) {
                $m->setDefaultDriver('swoole');
                Fly::getServer()->echo('config session.driver=swoole');
            }

            return $m;

        });
    }
}
