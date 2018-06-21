<?php

namespace LaravelFly\Providers;

use Illuminate\Support\ServiceProvider;

class CommandsServiceProvider extends ServiceProvider
{
    public function register()
    {
        // overwrite artisan-command config.cache
        $this->app->extend('command.config.cache', function ($old,$app) {
            return new ConfigCacheCommand($app['files']);
        });

        // overwrite artisan-command config.clear
        $this->app->extend('command.config.clear', function ($old,$app) {
            return new ConfigClearCommand($app['files']);
        });
    }

    public function boot()
    {
        // php artisan vendor:publish --tag=fly-app
        $this->publishes([
            __DIR__ . '/../../../config/laravelfly-app-config.example.php' => config_path('laravelfly.php'),
        ], 'fly-app');

        // php artisan vendor:publish --tag=fly-server
        $this->publishes([
            __DIR__ . '/../../../config/laravelfly-server-config.example.php' => base_path('fly.conf.php'),
        ], 'fly-server');
    }
}