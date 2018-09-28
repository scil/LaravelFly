<?php
namespace LaravelFly\Map\Illuminate\Redis;

use Illuminate\Support\Arr;

class RedisServiceProvider extends  \Illuminate\Redis\RedisServiceProvider
{
    public function register()
    {
        $this->app->singleton('redis', function ($app) {
            $config = $app->make('config')->get('database.redis');

            // hack
            return new RedisManager(Arr::pull($config, 'client', 'predis'), $config);
        });

        $this->app->bind('redis.connection', function ($app) {
            return $app['redis']->connection();
        });
    }

}