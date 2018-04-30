<?php


namespace LaravelFly\Tests\Map\LaravelTests;

use LaravelFly\Tests\Map\MapTestCase as Base;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\Test;
use Symfony\Component\EventDispatcher\GenericEvent;


class TestCase extends Base
{
    static $laravelTestsDir = __DIR__ . '/../../../vendor/laravel/framework/tests';

    static $tmpdir='/tmp/laravellfy_tests';

    static $classMap=[
       'Illuminate\View\Factory' => 'LaravelFly\Map\Illuminate\View\Factory',
    ];

    function testDir()
    {
        self::assertTrue(is_dir(static::$laravelTestsDir), ' or git clone -b 5.5 https://github.com/laravel/framework.git ');

        @mkdir(self::$tmpdir);
        self::assertTrue(is_dir(self::$tmpdir), "ensure /tmp/laravelfly_tests exists");

    }

    function copyAndSed($subDir){

        $srcDir= static::$laravelTestsDir.$subDir;
        $testsBaseir = static::$tmpdir;
        $testsDir= $testsBaseir.$subDir;

        $cmd="cp -f -r $srcDir $testsBaseir";
        passthru($cmd,$r);
        if($r!==0){
            self::fail("faild cmd: $cmd");
        }

        $sedFile =  __DIR__. '/class_replace_sed.txt';
        $cmd = "sed -i -f $sedFile  `find $testsDir -type f `";
        passthru($cmd,$r);
        if($r!==0){
            self::fail("faild cmd: $cmd");
        }

        return $testsDir;

    }

    function testViewInWorker()
    {
        $testsDir=$this->copyAndSed('/View');

        self::assertTrue(is_dir($testsDir), "ensure /tmp/laravelfly_tests exists");

        static::makeNewServer(['LARAVELFLY_MODE' => 'Map'], ['worker_num' => 1]);

        static::$chan = $chan = new \Swoole\Channel(1024 * 256);

        static::$dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($chan, $testsDir) {
//            $appR = new \ReflectionObject($event['app']);
//            $corDictR = $appR->getProperty('corDict');
//            $corDictR->setAccessible(true);
//            $instances = $corDictR->getValue()[WORKER_COROUTINE_ID]['instances'];

            ob_start();
            $testRunner = new TestRunner();
            $test = $testRunner->getTest($testsDir, '', 'Test.php');
            $testRunner->doRun($test, ['stopOnError' => true], false);
            $result = ob_get_clean();
            $chan->push($result);

            sleep(3);
            $event['server']->getSwooleServer()->shutdown();

        });

        static::$server->start();

        echo "\n[[SWOOLE TEST RESULT]]\n", $chan->pop();


    }


}