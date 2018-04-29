<?php

namespace LaravelFly\Tests\Map\Unit\Server\Traits;

use Illuminate\Support\Facades\Artisan;
use LaravelFly\Tests\Unit\Server\CommonServerTestCase;
use Symfony\Component\EventDispatcher\GenericEvent;

class WorkerTest extends CommonServerTestCase
{

    function testDownFile()
    {
        $server = static::getCommonServer();
        $this->resetServerConfigAndDispatcher();

        $app = static::getLaravelApp();

        /**
         * @var \Illuminate\Foundation\Console\Kernel
         */
        $art = $app->make('\Illuminate\Foundation\Console\Kernel');
        @unlink($server->getDownFileDir() . '/down');
        self::assertFalse($app->isDownForMaintenance());

        $options = [
            // use two process for two workers, worker 0 used for watchDownFile, worker 1 used for phpunit
            'worker_num' => 2,
            'listen_port' => 9503,
            'daemonize' => false,
            'log_file' => $server->path('/storage/logs/swoole.log'),
            'compile' => false
        ];
        $server->config($options);

        $dispatcher = $server->getDispatcher();

        // use assert in server, these tests can not reported by phpunit , but if assert failed, error output in console
        $dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($server, $app, $art) {

            /**
             * worker 0 used for watchDownFile, worker 1 used for phpunit
             *
             * if only one worker,closure added by swoole_event_add in watchDownFile() only run
             * after the closure here finish
             * $server->getMemory('isDown') will not change.
             *
             */
            if($event['workerid']===0) return;

            self::assertEquals(0, $server->getMemory('isDown'));

            $art->call('down');
            sleep(1);
            self::assertTrue($app->isDownForMaintenance());
            file_put_contents($server->path('storage/framework/ok3'),$server->getMemory('isDown'));
            self::assertEquals(1, $server->getMemory('isDown'));


            $art->call('up');
            sleep(1);
            self::assertFalse($app->isDownForMaintenance());
            sleep(1);
            self::assertEquals(0, $server->getMemory('isDown'));

            $server->getSwooleServer()->shutdown();
        });

        $swoole_server = $this->setSwooleForServer($options);

        $server->start();

    }
}
