<?php


namespace LaravelFly\Tests\Map\Unit;

use LaravelFly\Tests\Map\MapTestCase;

class FlyFilesTest extends MapTestCase
{

    function testInitEnv()
    {
        self::assertEquals(false, class_exists('Illuminate\Container\Container', false));
        self::assertEquals(false, class_exists('Illuminate\Foundation\Application ', false));

        $options= ['mode'=>'Map','log_cache'=>2];
        \LaravelFly\Server\Common::includeFlyFiles($options);


        self::assertEquals(true, class_exists('Illuminate\Foundation\Application', false));

    }

}