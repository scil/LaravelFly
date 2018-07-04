<?php


namespace LaravelFly\Tests\Map\Unit;

use LaravelFly\Tests\Map\MapTestCase;

class FlyFilesTest extends MapTestCase
{


    function testFlyFiles()
    {
        $map = \LaravelFly\Server\Common::getAllFlyMap();
        $number = count($map);

        self::assertEquals(12, $number);

        // 5 files in a dir, and plus . an ..
        self::assertEquals(10, count(scandir($this->flyDir, SCANDIR_SORT_NONE)));

        // plus a Kernel.php
        self::assertEquals(11, count(scandir($this->backOfficalDir, SCANDIR_SORT_NONE)));

        foreach ($map as $f => $originLocation) {
            self::assertEquals(true, is_file($this->flyDir . $f), "{$this->flyDir}.$f");
            self::assertEquals(true, is_file($this->backOfficalDir . $f));
            self::assertEquals(true, is_file(static::$workingRoot . $originLocation));
        }
    }

    function testCompareFilesContent()
    {
        $this->compareFilesContent( \LaravelFly\Server\Common::getAllFlyMap());
    }

    function testInitEnv()
    {
        self::assertEquals(false, class_exists('Illuminate\Container\Container', false));
        self::assertEquals(false, class_exists('Illuminate\Foundation\Application ', false));

        $options= ['mode'=>'Map','log_cache'=>2];
        \LaravelFly\Server\Common::includeFlyFiles($options);


        self::assertEquals(true, class_exists('Illuminate\Foundation\Application', false));

    }

}