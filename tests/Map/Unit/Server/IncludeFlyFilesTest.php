<?php


namespace LaravelFly\Tests\Map\Unit;

use LaravelFly\Tests\Map\MapTestCase;

class IncludeFlyFilesTest extends MapTestCase
{

    function testInitEnv()
    {
        $r = self::processGetArray(function () {
            $r[] = class_exists('Illuminate\Container\Container', false);
            $r[] = class_exists('Illuminate\Foundation\Application ', false);

            $options = ['mode' => 'Map', 'log_cache' => 2];
            \LaravelFly\Server\Common::includeFlyFiles($options);


            $r[] = class_exists('Illuminate\Foundation\Application', false);
            return $r;
        });

        self::assertEquals([false,false,true], $r);
    }

}