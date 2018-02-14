<?php

namespace LaravelFly\Dict\Bootstrap;

use LaravelFly\Dict\Application;
class RegisterAcrossProviders
{
    public function bootstrap(Application $app)
    {
        $app->registerAcrossProviders();
    }
}
