<?php

namespace LaravelFly\Coroutine;

use Illuminate\Foundation\Http\Events;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application implementation.
     *
     * @var \LaravelFly\Coroutine\Application
     */
    protected $app;

    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,

        \LaravelFly\Coroutine\Bootstrap\SetProvidersInRequest::class,

        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,

//        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
//        \Illuminate\Foundation\Bootstrap\BootProviders::class,
        \LaravelFly\Coroutine\Bootstrap\RegisterAndBootProvidersOnWork::class,

        //todo
//        \LaravelFly\Greedy\Bootstrap\FindViewFiles::class,

    ];

}