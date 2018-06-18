<?php


namespace LaravelFly\Tests\Map\Unit;

use LaravelFly\Tests\Map\MapTestCase;

class FlyFilesTest extends MapTestCase
{

    protected $flyDir = __DIR__ . '/../../../../src/fly/';

    protected $backOfficalDir = __DIR__ . '/../../../offcial_files/';

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

        $diffOPtions = '--ignore-all-space --ignore-blank-lines';

        $same = true;

        foreach (\LaravelFly\Server\Common::getAllFlyMap() as $back => $offcial) {
            $back = $this->backOfficalDir . $back;
            $offcial = static::$laravelAppRoot . $offcial;
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

        $options= ['mode'=>'Map','log_cache'=>2];
        \LaravelFly\Server\Common::includeFlyFiles($options);


        self::assertEquals(true, class_exists('Illuminate\Foundation\Application', false));

    }

}