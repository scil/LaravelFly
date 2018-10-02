<?php
namespace LaravelFly\Map\CommonHack;


trait Kernel
{
    protected function dispatchToRouter()
    {
        return function ($request) {

            if (!(LARAVELFLY_SERVICES['request']))
                $this->app->instance('request', $request);

            return $this->router->dispatch($request);
        };
    }

}