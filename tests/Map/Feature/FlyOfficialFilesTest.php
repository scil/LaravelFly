<?php


namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Server\Common;
use LaravelFly\Tests\BaseTestCase as Base;

class FlyOfficialFilesTest extends Base
{

    static $map;

    function testVars()
    {
        self::assertDirectoryExists(static::$laravelVersionAppRoot, 'please set proper ENV var LARAVEL_VERSION_PROJECT_ROOT in phpunit.xml');
    }

    function testFlyFiles()
    {
        static::$map = $map = $this->processGetArray(function () {
            return Common::getFlyMap();
        });

        $flyFilesNumber = 25;

        self::assertEquals($flyFilesNumber, count($map));

        // +3: . an .. and FileViewFinderSameView.php
        // +1: 'Database/Eloquent/Concerns/HasRelationships.php'  not used, but available
        // -4: 5 files in a dir    ViewConcerns
        // -1: 2 files in a dir        Database
        // -2: 3 files in a dir        Foundation
        // -1: 2 files in a dir        Routing
        // -1: Kernel.php
        $topNumber = $flyFilesNumber + 3 + 1 - 4 - 1 - 2 - 1 - 1;
        self::assertEquals($topNumber, count(scandir(static::$flyDir, SCANDIR_SORT_NONE)));

        // +3: another kernel.php whoses class is App\Http\Kernel.php
        //     Http/
        //     extended/
        // -1: FileViewFinderSameView.php
        self::assertEquals($topNumber + 3 - 1, count(scandir(static::$backOfficalDir, SCANDIR_SORT_NONE)));

        foreach ($map as $f => $originLocation) {

            self::assertEquals(true, is_file(static::$backOfficalDir . $f));

            if ($f !== 'Http/Kernel.php')
                self::assertEquals(true, is_file(static::$flyDir . $f), static::$flyDir . $f);

            $ff = static::$workingRoot . $originLocation;
            self::assertEquals(true, is_file($ff), "is file: $ff");
        }
    }

    function testCompareFilesContent()
    {
        $map = static::$map;
        $this->compareFilesContent($map);
    }


    function testCompareFilesContentLocal()
    {

        $origin = [
            static::$flyDir,
            static::$backOfficalDir,
            static::$laravelVersionAppRoot

        ];
        static::$flyDir = FLY_ROOT . '/src/fly/';
        static::$backOfficalDir = FLY_ROOT . '/tests/offcial_files/';
        static::$laravelVersionAppRoot = static::$laravelAppRoot;


        $map = [
            // Kernel with Dict version should go with Kernel without Dict
            'Kernel.php' => '/vendor/scil/laravel-fly/src/LaravelFly/Map/Kernel.php',

        ];
        $this->compareFilesContent($map);

        list(
            static::$flyDir,
            static::$backOfficalDir,
            static::$laravelVersionAppRoot
            ) = $origin;

    }

}