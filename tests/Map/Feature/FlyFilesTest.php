<?php


namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Tests\Map\MapTestCase;

class FlyFilesTest extends MapTestCase
{


    function testFlyFiles()
    {
        $map = \LaravelFly\Server\Common::getAllFlyMap();
        $number = count($map);
        $flyFilesNumber = 16;

        // +2: Repository.php and SimpleRepository.php, FileViewFinder.php and FileViewFinderSameView.php
        self::assertEquals($flyFilesNumber, $number + 2);

        // 5 files in a dir,  2 Repository in a dir, and plus . an ..
        self::assertEquals($flyFilesNumber - 4 -1 + 2, count(scandir($this->flyDir, SCANDIR_SORT_NONE)));

        // plus another kernel.php whoses class is App\Http\Kernel.php
        self::assertEquals($flyFilesNumber - 4 -1  + 2 + 1, count(scandir($this->backOfficalDir, SCANDIR_SORT_NONE)));

        foreach ($map as $f => $originLocation) {
            self::assertEquals(true, is_file($this->flyDir . $f), "{$this->flyDir}.$f");
            self::assertEquals(true, is_file($this->backOfficalDir . $f));
            //var_dump($originLocation);
            self::assertEquals(true, is_file(static::$workingRoot . $originLocation));
        }
    }

    function testCompareFilesContent()
    {
        $this->compareFilesContent(\LaravelFly\Server\Common::getAllFlyMap());
    }

}