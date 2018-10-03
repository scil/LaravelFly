<?php


namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Server\Common;
use LaravelFly\Tests\BaseTestCase as Base;

class FlyOfficialFilesTest extends Base
{

    static $map;

    function testFlyFiles()
    {
        static::$map = $map = $this->processGetArray(function (){
            return Common::getFlyMap();
        });

        $flyFilesNumber = 19;

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

            if ($f !== 'Http/Kernel.php')
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


    function testCompareFilesContentLocal()
    {

        static::$flyDir = FLY_ROOT . '/src/fly/';
        static::$backOfficalDir = FLY_ROOT . '/tests/offcial_files/';


        $map = [
            // Kernel with Dict version should go with Kernel without Dict
            'Kernel.php'=> '/vendor/scil/laravel-fly/src/LaravelFly/Map/Kernel.php',

        ];
        $this->compareFilesContent($map);


    }

}