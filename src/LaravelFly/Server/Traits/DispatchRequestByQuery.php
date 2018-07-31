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
            $this->echo('worker_num is 1, dispatch_by_query is useless','INFO');
            return;
        }

        if (isset($options['dispatch_func'])) {
            $this->echo('dispatch_func is set, dispatch_by_query is disabled','INFO');
            return;
        }

        $options['dispatch_func'] = [$this, 'dispatch'];

        $this->createWorkerIds($options);
    }

    // must be public
    public function dispatch($swoole_server, $fd, $type, $data)
    {
        if (preg_match('/worker-(id|pid)(?:=|:\s+)(\d+)/i', $data, $matches)) {

            // 'id' or 'Id', neither 'pid' or 'Pid'
            if (strlen($matches[1]) === 2) {
                $id = (int)($matches[2]) % $swoole_server->setting['worker_num'];
                $this->echo("dispatch worker $id by worker-id={$matches[2]}",'INFO');
                return $id;
            }

            foreach ($swoole_server->fly->getWorkerIds() as $row) {
                if ($row['pid'] == $matches[2]) {
                    $id = $row['id'];
                    $this->echo("dispatch worker $id by worker-pid={$matches[2]}");
                    return $id;
                }
            }

        }

        $this->echo("dispatch worker by $fd % {$swoole_server->setting['worker_num']}");
        return $fd % $swoole_server->setting['worker_num'];
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
