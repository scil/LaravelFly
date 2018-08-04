<?php

namespace LaravelFly\Tests\Map\Unit\Server\Traits;

use LaravelFly\Tests\BaseTestCase as Base;

use Symfony\Component\EventDispatcher\GenericEvent;

class DispatchRequestByQueryTest extends Base
{
    function testDispatchCallbackByWorkerId()
    {


        $data = [
            ['worker_num' => 5, 'raw' => 'GET /fly?worker-id=0 HTTP/1.1', 'fd' => 99, 'selected' => 0],
            ['worker_num' => 5, 'raw' => 'GET /fly?worker-id=2 HTTP/1.1', 'fd' => 99, 'selected' => 2],
            ['worker_num' => 5, 'raw' => 'GET /fly?worker-id=3 HTTP/1.1', 'fd' => 99, 'selected' => 3],
            ['worker_num' => 5, 'raw' => "GET /fly HTTP/1.1\nWorker-Id: 1", 'fd' => 99, 'selected' => 1],
            ['worker_num' => 5, 'raw' => "GET /fly HTTP/1.1\nWorker-Id: 9", 'fd' => 99, 'selected' => 4],
        ];

        $constances = [
        ];

        $step = 0;

        foreach ($data as $one) {

            $step += 1;
            $options = [
                'worker_num' => $one['worker_num'],
                'pre_include' => false,
                'mode' => 'Backup',
                'dispatch_by_query' => true,
                'listen_port' => 9891 + $step,
            ];

            $r = self::createFlyServerInProcess($constances, $options, function (\LaravelFly\Server\ServerInterface $server) use ($one) {
                return $server->dispatch($server->getSwooleServer(), $one['fd'], '', $one['raw']);
            }, 1);

            $r = (int)last(explode("\n", $r));

            self::assertEquals($one['selected'], $r);

        }
    }

    function testDispatchCallbackByWorkerPid()
    {

        $constances = [
        ];

        $data = [
            ['worker_num' => 5, 'raw' => 'GET /fly?worker-pid=%d HTTP/1.1', 'fd' => 99],
            ['worker_num' => 5, 'raw' => "GET /fly HTTP/1.1\nWorker-Pid: %d", 'fd' => 99],
        ];


        $step = 0;
        foreach ($data as $one) {
            $step += 1;

            $options = [
                'dispatch_by_query' => true,
                'worker_num' => $one['worker_num'],
                'pre_include' => false,
                'mode' => 'Backup',
                'listen_port' => 9991 + $step,
            ];


            $r = self::createFlyServerInProcess($constances, $options, function ($server) use ($one, $options) {

                $server->config($options);

                $dispatcher = $server->getDispatcher();
                /**
                 *  stop run this statement: static::$workerIds->del($event['workerid']);
                 *  then $server->getWorkerIds() can get the data even swoole is shutdown
                 */
                $dispatcher->addListener('worker.stopped', function (GenericEvent $event) {
                    $event->stopPropagation();
                }, 9);


                $dispatcher->addListener('worker.ready', function (GenericEvent $event) use ($server) {

                    $ids = $server->getWorkerIds();
                    if (count($ids) === $server->getConfig('worker_num')) {

                        $server->getSwooleServer()->shutdown();
                    }
                });

                $server->start();

                $ids = $server->getWorkerIds();

                $selected_worker_id = random_int(0, $one['worker_num'] - 1);

                $a_line = $ids->get($selected_worker_id);

                return json_encode([
                    'to_select' => $selected_worker_id,
                    'id' => $a_line['id'],
                    'selected' => $server->dispatch($server->getSwooleServer(),
                        $one['fd'], '',
                        sprintf($one['raw'], $a_line['pid'])
                    )
                ]);


            }, 1);

//            $r = (int)last(explode("\n", $r));

            $r = json_decode($r);

            $selected_worker_id = $r->to_select;
            self::assertEquals($selected_worker_id, $r->id);
            self::assertEquals($selected_worker_id, $r->selected);

        }


    }

}