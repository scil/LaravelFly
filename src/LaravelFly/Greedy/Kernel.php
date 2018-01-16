<?php

namespace LaravelFly\Greedy;


class Kernel extends \LaravelFly\Normal\Kernel
{

    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,

        \LaravelFly\Normal\Bootstrap\CleanProviders::class,

        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,

//        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
//        \Illuminate\Foundation\Bootstrap\BootProviders::class,
        \LaravelFly\Greedy\Bootstrap\RegisterAndBootProvidersOnWork::class,
        \LaravelFly\Greedy\Bootstrap\FindViewFiles::class,
        /**
         * prevent providers to run twice,see :$this->>bootProvidersInRequest
         *      `array_walk($this->serviceProviders,`
         *
         * this item must be after the one above
         * because 'app()->make' would change 'app()->serviceProviders'
         */
        \LaravelFly\Greedy\Bootstrap\ResetServiceProviders::class,
        //todo
        // bug: reg should be called before any boot, but the RegisterAndBootProvidersOnWork is above
        \LaravelFly\Greedy\Bootstrap\RegisterProvidersAcross::class,

        \LaravelFly\Normal\Bootstrap\SetBackupForBaseServices::class,
        \LaravelFly\Normal\Bootstrap\BackupConfigs::class,
        \LaravelFly\Normal\Bootstrap\BackupAttributes::class,
    ];
}