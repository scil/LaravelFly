<?php

namespace LaravelFly\Coroutine\Bootstrap;

use LaravelFly\Coroutine\Application;
class RegisterAcrossProviders
{
    public function bootstrap(Application $app)
    {
        $app->registerAcrossProviders();
    }
}
