<?php
namespace LaravelFly\Map\CommonHack;


trait Kernel
{
    protected function dispatchToRouter()
    {
        return function ($request) {

            // if (!(LARAVELFLY_SERVICES['request'])) $this->app->instance('request', $request);

            /**
             * @var $this \LaravelFly\Map\Kernel
             */
            return $this->router->dispatch($request);
        };
    }

}