<?php


namespace LaravelFly\Tests\Map\LaravelTests;

use LaravelFly\Tests\Map\MapTestCase as Base;
use Symfony\Component\EventDispatcher\GenericEvent;


class TestCase extends Base
{
    static $laravelTestsDir = __DIR__ . '/../../../vendor/laravel/framework/tests';

    static $tmpdir = '/tmp/laravellfy_tests';

    static $classMap = [
        'Illuminate\View\Factory' => 'LaravelFly\Map\Illuminate\View\Factory',
    ];

    function testDir()
    {
        self::assertTrue(is_dir(static::$laravelTestsDir), ' or git clone -b 5.6 https://github.com/laravel/framework.git ');

        @mkdir(self::$tmpdir);
        self::assertTrue(is_dir(self::$tmpdir), "ensure /tmp/laravelfly_tests exists");

    }

    function copyAndSed($subDir)
    {

        $srcDir = static::$laravelTestsDir . $subDir;
        $testsBaseir = static::$tmpdir;
        $testsDir = $testsBaseir . $subDir;

        $cmd = "cp -f -r $srcDir $testsBaseir";
        passthru($cmd, $r);
        if ($r !== 0) {
            self::fail("faild cmd: $cmd");
        }

        $sedFile = __DIR__ . '/class_replace_sed.txt';
        $cmd = "sed -i -f $sedFile  `find $testsDir -type f `";
        passthru($cmd, $r);
        if ($r !== 0) {
            self::fail("faild cmd: $cmd");
        }

        return $testsDir;

    }

    private function testViewInWorker()
    {
        $testsDir = $this->copyAndSed('/View');


        $r = $this->dirTestOnFlyServer($testsDir);


        echo "\n[[LARAVEL TEST RESULT]]\n", $r;


    }

    function testFoundationInWorker()
    {
//        $testsDir = $this->copyAndSed('/Foundation');
        $testsDir = static::$laravelTestsDir . '/Foundation';

        $r = $this->dirTestOnFlyFiles($testsDir);

        echo "\n[[LARAVEL TEST RESULT]]\n", $r;


    }

    function est1()
    {
        echo 'test:', self::process(function () {
            echo 33333333;
        });
    }
}