<?php

namespace LaravelFly\Greedy;


class Kernel extends \LaravelFly\Kernel
{

    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,

        \LaravelFly\Bootstrap\SetProvidersInRequest::class,

        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,

//        'Illuminate\Foundation\Bootstrap\RegisterProviders',
//        'Illuminate\Foundation\Bootstrap\BootProviders',


        'LaravelFly\Greedy\Bootstrap\RegisterAndBootProvidersInWork',
        'LaravelFly\Greedy\Bootstrap\FindViewFiles',

        /**
         * prevent providers to run twice,see :$this->>bootProvidersInRequest
         *      `array_walk($this->serviceProviders,`
         *
         * this item must be after the one above
         * because 'app()->make' would change 'app()->serviceProviders'
         */
        'LaravelFly\Greedy\Bootstrap\ResetServiceProviders',
        'LaravelFly\Greedy\Bootstrap\RegisterProvidersAcross',

        \LaravelFly\Bootstrap\SetBackupForBaseServices::class,
        \LaravelFly\Bootstrap\BackupConfigs::class,
        \LaravelFly\Bootstrap\BackupAttributes::class,
    ];
}