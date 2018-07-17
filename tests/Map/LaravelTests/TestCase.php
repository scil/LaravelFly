<?php


namespace LaravelFly\Tests\Map\LaravelTests;

use LaravelFly\Server\Common;
use LaravelFly\Tests\Map\MapTestCase as Base;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\Test;
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

    function ob_test($testsDir)
    {

        ob_start();
        $testRunner = new TestRunner();
        $test = $testRunner->getTest($testsDir, '', 'Test.php');
        $testRunner->doRun($test, ['stopOnError' => true], false);
        return ob_get_clean();
    }

    function ob_test_include($testsDir, $chan, $app=false)
    {
        self::assertTrue(is_dir($testsDir), "ensure $testsDir exists");

        foreach ([
                     'LARAVELFLY_MODE' => 'Map',
                     'WORKER_COROUTINE_ID'=>-1,
                     'LARAVELFLY_SERVICES' => [
                         'config' => false,
                         'kernel' => false,
                         'hash' => false,
                         'view.finder'=>false,

                     ]
                 ] as $name => $val) {
            if (!defined($name))
                define($name, $val);
        }

        $options = ['mode' => 'Map', 'log_cache'=>true,];

        Common::includeFlyFiles($options);

        if($app){
            $commonServer = new \LaravelFly\Server\Common();
            $commonServer->_makeLaravelApp();
        }

        $result = $this->ob_test($testsDir);
        $chan->push($result);
    }

    function ob_test_server($testsDir, $chan)
    {
        static $step = 0;
        $step += 1;

        self::assertTrue(is_dir($testsDir), "ensure $testsDir exists");

        static::makeNewFlyServer(['LARAVELFLY_MODE' => 'Map'], [
            'worker_num' => 1,
            'listen_port' => 9601 + $step,
            // EARLY
            'early_laravel' => true
        ]);


        $result = $this->ob_test($testsDir);
        $chan->push($result);
    }

    function testViewInWorker()
    {
        $testsDir = $this->copyAndSed('/View');

        $chan = new \Swoole\Channel(1024 * 256);

        // EARLY
        // static::$dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($chan, $testsDir) {

        $this->ob_test_server($testsDir, $chan);

        // EARLY
        //    sleep(3);
        //    $event['server']->getSwooleServer()->shutdown();
        // });
        // static::$flyServer->start();

        echo "\n[[SWOOLE TEST RESULT]]\n", $chan->pop();


    }

    function testFoundationInWorker()
    {
        $testsDir = $this->copyAndSed('/Foundation');

        $chan = new \Swoole\Channel(1024 * 256);

        // EARLY
        // static::$dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($chan, $testsDir) {

        $this->ob_test_include($testsDir, $chan);

        // EARLY
        //    sleep(3);
        //    $event['server']->getSwooleServer()->shutdown();
        // });
        // static::$flyServer->start();

        echo "\n[[SWOOLE TEST RESULT]]\n", $chan->pop();


    }

}