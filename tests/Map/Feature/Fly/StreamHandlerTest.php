<?php

namespace LaravelFly\Tests\Map\Feature;

use LaravelFly\Tests\Map\MapTestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class StreamHandlerTest extends MapTestCase
{

    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::makeNewFlyServer(['LARAVELFLY_MODE' => 'Map'], ['worker_num' => 1,'pre_include'=>false]);

        static::$chan = $chan = new \Swoole\Channel(1024 * 256);

        static::$dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($chan) {
            function stripLogRecord($file)
            {
                $new = [];
                foreach (file($file) as $line) {
                    $eles = mb_split('\s+', $line);
                    $new[] = $eles[3] ?? "\n";
                }
                return implode('', $new);
            }

            $app = $event['app'];
            $log = $app->make('log');
            $logFile = $app->storagePath() . '/logs/laravel.log';
            @unlink($logFile);

            $i = 1;
            while ($i < 5) {
                $log->info($i);
                $i++;
            }
            $chan->push(stripLogRecord($logFile));

            $log->info($i++); // 5

            $chan->push(stripLogRecord($logFile));

            while ($i < 11) {
                $log->info($i);
                $i++;
            }
            $chan->push(stripLogRecord($logFile));

            $log->info($i); // 11
            $chan->push(stripLogRecord($logFile));

            /**
             * @var \swoole_http_server
             */
            $swoole = $event['server']->getSwooleServer();

//            /**  for old version of swoole
//             * ensure onWorkerStop is executed, then event 'worker.stopped' dispatched, then  cacheWrite() executed
//             * This is only needed when `$swoole->shutdown()`.
//             * I have no idea why sending signal SIGTERM allow onWorkerStop, while shutdown not allow
//             */
//            $swoole->stop($event['workerid'], true);

            $swoole->shutdown();
        });

        static::$flyServer->start();

    }

    function testLog()
    {
        $chan = static::$chan;
        // no write
        self::assertEquals("", $chan->pop());
        // write , as $flyCacheMax == 5
        self::assertEquals("12345\n", $chan->pop());
        self::assertEquals("12345\n678910\n", $chan->pop());
        // no change, 11 is in cache
        self::assertEquals("12345\n678910\n", $chan->pop());

        $logFile = static::getFlyServer()->path('storage/logs/laravel.log');

        // after shutdown
        self::assertEquals("12345\n678910\n11\n", $this->stripLogRecord($logFile),'after shutdown, see reason :
        // todo why?
       in src/fly/StreamHandler.php 
        ');

    }

    /**
     * extract 9 from this string:
     *      [2018-04-13 00:03:32] testing.INFO: 9
     *
     * @param $file
     * @return string
     */
    function stripLogRecord($file)
    {
        $new = [];
        foreach (file($file) as $line) {
            $eles = mb_split('\s+', $line);
            $new[] = $eles[3] ?? "\n";
        }
        return implode('', $new);
    }
}

