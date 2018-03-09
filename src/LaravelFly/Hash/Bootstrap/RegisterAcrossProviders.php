<?php

namespace LaravelFly\Hash\Bootstrap;

use LaravelFly\Hash\Application;
class RegisterAcrossProviders
{
    public function bootstrap(Application $app)
    {
        $app->registerAcrossProviders();
    }
}
