<?php


namespace LaravelFly\Tests\Map;

use LaravelFly\Tests\BaseTestCase as Base;


define('LARAVELFLY_MODE', 'Map');

class MapTestCase extends Base
{
    /**
     * @var \Swoole\Channel
     */
    static protected $chan;

    /**
     * forbidden this function, as it will load official Illuminate\Container\Container
     * which conflict with src\fly\Container.php
     * @return \Illuminate\Foundation\Application|void
     */
    static protected function getLaravelApp()
    {

    }

}