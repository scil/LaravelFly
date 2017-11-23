<?php
/**
 * Created by PhpStorm.
 * User: ivy
 * Date: 2015/7/29
 * Time: 21:59
 */

namespace LaravelFly\Greedy;


class Kernel extends \LaravelFly\Kernel
{

    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,

        'LaravelFly\Bootstrap\SetProvidersInRequest',

        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,

//        'Illuminate\Foundation\Bootstrap\RegisterProviders',
//        'Illuminate\Foundation\Bootstrap\BootProviders',


        'LaravelFly\Greedy\Bootstrap\RegisterAndBootProvidersInWork',
        'LaravelFly\Greedy\Bootstrap\FindViewFiles',
        'LaravelFly\Bootstrap\MakeAndSetBackupForServicesInWorker',

        /**
         * this item must be after the one above
         * because 'app()->make' would change 'app()->serviceProviders'
         */
        'LaravelFly\Greedy\Bootstrap\ResetServiceProviders',
        'LaravelFly\Greedy\Bootstrap\RegisterProvidersAcross',

        'LaravelFly\Bootstrap\BackupConfigs',
        'LaravelFly\Bootstrap\BackupAttributes',
    ];
}