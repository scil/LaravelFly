<?php

namespace LaravelFly\Map\Illuminate\Database;

//use Hhxsv5\LaravelS\Illuminate\Database\ConnectionFactory;
use Hhxsv5\LaravelS\Illuminate\Database\DatabaseManager;

class DatabaseServiceProvider extends \Illuminate\Database\DatabaseServiceProvider
{

    static public function coroutineFriendlyServices():array
    {
        return ['db.factory','db'];
    }
    /**
     * Register the primary database bindings.
     *
     * @return void
     */
    protected function registerConnectionServices()
    {
        $this->app->singleton('db.factory', function ($app) {
            // hack
            return new ConnectionFactory($app);
        });

        $this->app->singleton('db', function ($app) {
            // hack
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });
    }


}
