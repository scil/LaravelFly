<?php

namespace LaravelFly\Map\Illuminate\Database;

//use Hhxsv5\LaravelS\Illuminate\Database\ConnectionFactory;
//use Hhxsv5\LaravelS\Illuminate\Database\DatabaseManager;

use Illuminate\Database\Eloquent\Model;

class DatabaseServiceProvider extends \Illuminate\Database\DatabaseServiceProvider
{

    static public function coroutineFriendlyServices(): array
    {
        return ['db.factory', 'db'];
    }


    public function register()
    {
        parent::register();

        // hack
        $this->initModel();
    }

    public function initModel()
    {
        Model::initStaticForCorontine(WORKER_COROUTINE_ID);

        foreach (config('laravelfly.models_booted_on_work') as $class) {
            if (class_exists($class)){
                new $class;
            }
        }
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
