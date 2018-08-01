<?php

namespace LaravelFly\Map\Illuminate\Database;

use Faker\Factory as FakerFactory;
use Faker\Generator as FakerGenerator;
//use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Queue\EntityResolver;
//use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Eloquent\QueueEntityResolver;
use Illuminate\Database\Eloquent\Factory as EloquentFactory;

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
        // hack
        $this->app->singleton('db.factory', function ($app) {
            return new Connectors\ConnectionFactory($app);
        });

        // hack
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function ($app) {
            return $app['db']->connection();
        });
    }


}
