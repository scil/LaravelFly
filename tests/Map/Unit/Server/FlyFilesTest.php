<?php


namespace LaravelFly\Tests\Map\Unit;

use LaravelFly\Tests\Map\BaseTestCase;

class FlyFilesTest extends BaseTestCase
{

    protected $flyDir = __DIR__ . '/../../../../src/fly/';

    protected $backOfficalDir = __DIR__ . '/../../../offcial_files/';

    function testFlyFiles()
    {
        $map = \LaravelFly\Server\Common::getAllFlyMap();
        $number = count($map);

        self::assertEquals(15, $number);

        // 5 files in a dir, and plus . an ..
        self::assertEquals(13, count(scandir($this->flyDir, SCANDIR_SORT_NONE)));

        // plus a Kernel.php
        self::assertEquals(14, count(scandir($this->backOfficalDir, SCANDIR_SORT_NONE)));

        foreach ($map as $f => $originLocation) {
            self::assertEquals(true, is_file($this->flyDir . $f), "{$this->flyDir}.$f");
            self::assertEquals(true, is_file($this->backOfficalDir . $f));
            self::assertEquals(true, is_file(static::$root . $originLocation));
        }
    }

    function testCompareFilesContent()
    {

        $diffOPtions = '--ignore-all-space --ignore-blank-lines';

        $same = true;

        foreach (\LaravelFly\Server\Common::getAllFlyMap() as $back => $offcial) {
            $back = $this->backOfficalDir . $back;
            $offcial = static::$root . $offcial;
            $cmdArguments = "$diffOPtions $back $offcial ";

            unset($a);
            exec("diff --brief $cmdArguments > /dev/null", $a, $r);
//            echo "\n\n[CMD] diff $cmdArguments\n\n";
//            print_r($a);
            if ($r !== 0) {
                $same = false;
                echo "\n\n[CMD] diff $cmdArguments\n\n";
                system("diff  $cmdArguments");
            }
        }

        self::assertEquals(true, $same);

    }

    function testInitEnv()
    {
        self::assertEquals(false, class_exists('Illuminate\Container\Container', false));
        self::assertEquals(false, class_exists('Illuminate\Foundation\Application ', false));

        $initEnv = new \ReflectionMethod('\LaravelFly\Server\Common', 'includeFlyFiles');
        $initEnv->setAccessible(true);
        $initEnv->invoke(null,['mode'=>'Map',]);


        self::assertEquals(true, class_exists('Illuminate\Foundation\Application', false));

    }

}