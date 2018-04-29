<?php


namespace LaravelFly\Tests\Map\LaravelTests;

use LaravelFly\Tests\Map\MapTestCase as Base;
use PHPUnit\TextUI\TestRunner;
use PHPUnit\Framework\Test;
use Symfony\Component\EventDispatcher\GenericEvent;


class TestCase extends Base
{
    static $laravelTestsDir = __DIR__ . '/../../../vendor/laravel/framework/tests';

    function testDir()
    {
        self::assertTrue(is_dir(static::$laravelTestsDir),' or git clone -b 5.5 https://github.com/laravel/framework.git ');
    }

    function testView()
    {

        static::makeNewServer(['LARAVELFLY_MODE' => 'Map'], ['worker_num' => 1]);

        static::$chan = $chan = new \Swoole\Channel(1024 * 256);

        static::$dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($chan) {
//            $appR = new \ReflectionObject($event['app']);
//            $corDictR = $appR->getProperty('corDict');
//            $corDictR->setAccessible(true);
//            $instances = $corDictR->getValue()[WORKER_COROUTINE_ID]['instances'];

            ob_start();
            $testRunner = new TestRunner();
            $test = $testRunner->getTest(static::$laravelTestsDir . '/View', '', 'Test.php');
            $testRunner->dorun($test, ['stopOnError' => true], false);
            $result = ob_get_clean();
            $chan->push($result);

            sleep(3);
            $event['server']->getSwooleServer()->shutdown();

        });

        static::$server->start();

        echo "\n[TEST RESULT]\n", $chan->pop();


    }


}