<?php

namespace LaravelFly\Map\Bootstrap;

use LaravelFly\Map\Application;
class RegisterAcrossProviders
{
    public function bootstrap(Application $app)
    {
        $app->registerAcrossProviders();
    }
}
