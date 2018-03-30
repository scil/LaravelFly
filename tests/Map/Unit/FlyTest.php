<?php


namespace LaravelFly\Tests\Map\Unit;

use LaravelFly\Tests\Map\BaseTestCase;

class FlyTest extends BaseTestCase
{
    protected $lastCheckedVersion = '5.5.33';

    protected $flyDir = __DIR__ . '/../../../src/fly/';

    protected $backOfficalDir = __DIR__ . '/../../offcial_files/';

    function testFlyFiles()
    {
        $map = \LaravelFly\Fly::$flyMap;
        $number = count($map);
        self::assertEquals(14, $number);
        // 5 files in a dir, and plus . an ..
        self::assertEquals(12, count(scandir($this->flyDir, SCANDIR_SORT_NONE)) );
        // plus a Kernel.php
        self::assertEquals(13, count(scandir($this->backOfficalDir, SCANDIR_SORT_NONE)) );

        foreach ($map as $f => $originLocation) {
            self::assertEquals(true, is_file($this->flyDir . $f));
            self::assertEquals(true, is_file($this->backOfficalDir . $f));
            self::assertEquals(true, is_file(static::$root . $originLocation));
        }
    }

    function testCompareFilesContent()
    {
        if (version_compare($this->lastCheckedVersion, static::getLaravelApp()->version()) >= 0) {
            return;
        }

        $diffOPtions = '--ignore-all-space --ignore-blank-lines';

        $same = true;

        foreach (\LaravelFly\Fly::$flyMap as $back => $offcial) {
            $back = $this->backOfficalDir . $back;
            $offcial = $this->getLaravelApp()->basePath() . $offcial;
            $cmdArguments = "$diffOPtions $back $offcial ";

            exec("diff --brief $cmdArguments > /dev/null", $a, $r);
            if ($r !== 0) {
                $same = false;
                echo "\n[CMD]diff $cmdArguments\n";
            }
        }

        self::assertEquals(true, $same);

    }

    function testInitEnv()
    {
        self::assertEquals(false, class_exists('Illuminate\Foundation\Application ', false));
        self::assertEquals(false, defined('WORKER_COROUTINE_ID '));

        $initEnv = new \ReflectionMethod('\LaravelFly\Fly', 'initEnv');
        $initEnv->setAccessible(true);
        $initEnv->invoke(null);

        self::assertEquals(true, defined('WORKER_COROUTINE_ID'));
        self::assertEquals(true, function_exists('tinker'));

        self::assertEquals(true, class_exists('Illuminate\Foundation\Application', false));

    }

}