<?php

namespace LaravelFly\Server\Traits;

use function foo\func;
use LaravelFly\Exception\LaravelFlyException as Exception;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

Trait DispatchRequestByQuery
{

    /**
     * @var \swoole_table
     */
    protected static $workerIds;

    protected function dispatchRequestByQuery(&$options)
    {
        if (empty($options['dispatch_by_query'])) return;

        if ($options['worker_num'] == 1) {
            echo '[INFO] worker_num is 1, dispatch_by_query is useless', PHP_EOL;
            return;
        }

        if (isset($options['dispatch_func'])) {
            echo '[INFO] dispatch_func is set, dispatch_by_query is disabled', PHP_EOL;
            return;
        }

        $options['dispatch_func'] = function ($serv, $fd, $type, $data) {
            if (preg_match('/worker-(id|pid)=(\d+)/i', $data, $matches)) {
                if ($matches[1] == 'id') {
                    return (int) ($matches[2]) % $serv->setting['worker_num'];
                } else {
                    foreach ($serv->fly->getWorkerIds() as $row) {
                        if ($row['pid'] == $matches[2]) {
                            return $row['id'];
                        }
                    }
                }
                return $fd % $serv->setting['worker_num'];
            }
        };

        $this->createWorkerIds($options);
    }

    protected function createWorkerIds($options)
    {
        static::$workerIds = $table = new \swoole_table($options['worker_num']);

        $table->column('id', \swoole_table::TYPE_INT, 1);
        $table->column('pid', \swoole_table::TYPE_INT, 3);
        $table->create();
        $this->workerIdsSubscriber();
    }

    function workerIdsSubscriber()
    {
        $this->dispatcher->addListener('worker.starting', function (GenericEvent $event) {
            static::$workerIds->set($event['workerid'], ['id' => $event['workerid'], 'pid' => getmypid()]);
        });
        $this->dispatcher->addListener('worker.stopped', function (GenericEvent $event) {
            static::$workerIds->del($event['workerid']);
        });
    }

    function getWorkerIds()
    {
        return static::$workerIds;
    }

}
