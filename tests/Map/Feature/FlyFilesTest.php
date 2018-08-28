<?php


namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Tests\BaseTestCase as Base;

class FlyFilesTest extends Base
{

    static $map;

    function test()
    {
        self::assertTrue(True);

        $r= self::processGetArray(function (){
            return \LaravelFly\Server\Common::getAllFlyMap();
        });

         static::$map = $r;
    }


    function testFlyFiles()
    {
        $map = static::$map;

        $number = count($map);
        $flyFilesNumber = 16;

        // +2: Repository.php and SimpleRepository.php, FileViewFinder.php and FileViewFinderSameView.php
        self::assertEquals($flyFilesNumber, $number + 2);

        // 5 files in a dir,  2 Repository in a dir, and plus . an ..
        self::assertEquals($flyFilesNumber - 4 -1 + 2, count(scandir(static::$flyDir, SCANDIR_SORT_NONE)));

        // plus another kernel.php whoses class is App\Http\Kernel.php
        self::assertEquals($flyFilesNumber - 4 -1  + 2 + 1, count(scandir(static::$backOfficalDir, SCANDIR_SORT_NONE)));

        foreach ($map as $f => $originLocation) {
            self::assertEquals(true, is_file(static::$flyDir . $f), static::$flyDir. $f);
            self::assertEquals(true, is_file(static::$backOfficalDir . $f));
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