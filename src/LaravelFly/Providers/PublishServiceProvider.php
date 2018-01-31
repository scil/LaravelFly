<?php

namespace LaravelFly\Providers;

use Illuminate\Support\ServiceProvider;

class PublishServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../../config/laravelfly-app-config.example.php' => config_path('courier.php'),
        ],'fly-app');

        $this->publishes([
            __DIR__ . '/../../../config/laravelfly-server-config.example.php' => base_path('laravelfly.server.config.php'),
        ],'fly-server');
    }
}