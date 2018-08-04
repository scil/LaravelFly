<?php
/**
 * use testcases provided by laravel framework
 */

namespace LaravelFly\Tests\Map\LaravelTests;

use LaravelFly\Tests\BaseTestCase as Base;
use Symfony\Component\EventDispatcher\GenericEvent;


class TestCase extends Base
{
    static $laravelTestsDir ;

    static $tmpdir = '/tmp/laravellfy_tests';

    static $classMap = [
        'Illuminate\View\Factory' => 'LaravelFly\Map\Illuminate\View\Factory',
    ];

    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::$laravelTestsDir = realpath(  __DIR__ . '/../../../vendor/laravel/framework/tests');
    }

    function testDir()
    {
        self::assertTrue(is_dir(static::$laravelTestsDir), static::$laravelTestsDir." not exists. \nmaybe need run\n:  git clone -b 5.6 https://github.com/laravel/framework.git ".
    realpath(__DIR__.'/../../../vendor/laravel'));

        @mkdir(self::$tmpdir);
        self::assertTrue(is_dir(self::$tmpdir), "ensure /tmp/laravelfly_tests exists");

    }

    function copyAndSed($subDir, $force=false)
    {

        $srcDir = static::$laravelTestsDir . $subDir;
        $testsBaseir = static::$tmpdir;
        $testsDir = $testsBaseir . $subDir;

        if(is_dir($testsDir) && !$force){
            echo "[FLY WARN] use old dir $testsDir\n";
            return $testsDir;
        }

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

        sleep(2);

    }

    function testFoundationInWorker()
    {
//        $testsDir = $this->copyAndSed('/Foundation');
        $testsDir = static::$laravelTestsDir . '/Foundation';

        // only in ob
        // $r = $this->dirTestInOb($testsDir, true);
        // in process
        $r = $this->dirTestOnFlyFiles($testsDir);

        echo "\n[[LARAVEL TEST RESULT]]\n", $r;

        sleep(1);

    }

    function est1()
    {
        echo 'test:', self::process(function () {
            echo 33333333;
        });
    }
}