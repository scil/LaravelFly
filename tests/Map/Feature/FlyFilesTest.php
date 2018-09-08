<?php


namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Tests\BaseTestCase as Base;

class FlyFilesTest extends Base
{

    static $map;

    function test()
    {
        self::assertTrue(True);

        $r = self::processGetArray(function () {
            return \LaravelFly\Server\Common::getAllFlyMap();
        });

        static::$map = $r;
    }


    function testFlyFiles()
    {
        $map = static::$map;

        $flyFilesNumber = 14;

        self::assertEquals($flyFilesNumber, count($map));

        // -4: 5 files in a dir,
        // -1: Kernel.php
        // +3: . an .. and FileViewFinderSameView.php
        self::assertEquals($flyFilesNumber - 4 - 1 + 3, count(scandir(static::$flyDir, SCANDIR_SORT_NONE)));

        // +3: another kernel.php whoses class is App\Http\Kernel.php
        //     Http/
        //     extended/
        // -1: FileViewFinderSameView.php
        self::assertEquals($flyFilesNumber - 4 - 1 + 3 + 3 - 1, count(scandir(static::$backOfficalDir, SCANDIR_SORT_NONE)));

        foreach ($map as $f => $originLocation) {

            self::assertEquals(true, is_file(static::$backOfficalDir . $f));
            if ($f === 'Http/Kernel.php')
                $f = '../Kernel.php';
            self::assertEquals(true, is_file(static::$flyDir . $f), static::$flyDir . $f);
            // var_dump(static::$workingRoot . $originLocation);
            self::assertEquals(true, is_file(static::$workingRoot . $originLocation));
        }
    }

    function testCompareFilesContent()
    {
        $map = static::$map;
        $this->compareFilesContent($map);
    }

}