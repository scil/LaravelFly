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
        'Illuminate\Foundation\Bootstrap\DetectEnvironment',
        'Illuminate\Foundation\Bootstrap\LoadConfiguration',
        'Illuminate\Foundation\Bootstrap\ConfigureLogging',
        'Illuminate\Foundation\Bootstrap\HandleExceptions',
        'Illuminate\Foundation\Bootstrap\RegisterFacades',

//        'Illuminate\Foundation\Bootstrap\RegisterProviders',
//        'Illuminate\Foundation\Bootstrap\BootProviders',

        'LaravelFly\Bootstrap\SetProvidersInRequest',

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