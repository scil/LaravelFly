<?php

namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Tests\BaseTestCase;

class OfficialFilesTest extends BaseTestCase
{
    protected $lastCheckedVersion = '5.5.33';


    function testCompareFiles()
    {
        if (version_compare($this->lastCheckedVersion, $this->getLaravelApp()->version()) >= 0) {
            return;
        }

        $backDir = __DIR__ . '/../offcial_files/';
        $diffOPtions = '--ignore-all-space --ignore-blank-lines';

        $same =true;

        foreach (\LaravelFly\Fly::$flyMap as $back => $offcial) {
            $back = $backDir . $back;
            $offcial = $this->getLaravelApp()->basePath() . $offcial;
            $cmdArguments = "$diffOPtions $back $offcial ";

            exec("diff --brief $cmdArguments > /dev/null", $a, $r);
            if ($r !== 0) {
                $same=false;
                echo "\ndiff $cmdArguments\n";
            }
        }

        self::assertEquals(true,$same);

    }
}

