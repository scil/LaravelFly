<?php

namespace LaravelFly\Server\Traits;

use Illuminate\Foundation\Bus\PendingDispatch;

trait Task
{
    function initForTask()
    {
        if (!isset($this->options['task_worker_num']) || $this->options['task_worker_num'] <= 0) return;

        $this->swoole->on('Task', [$this, 'onTask']);
        $this->swoole->on('Finish', [$this, 'onFinish']);

        static::includeConditionFlyFiles('task');
        PendingDispatch::$swooleServer = $this->swoole;

    }

    function onTask($server, $task_id, $from_id, $data)
    {
        echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=". "." . PHP_EOL;
        var_dump($data);
        if(method_exists($data,'handle')){
            $data->handle();
        }
    }

    function onFinish($server, $task_id, $data)
    {
        echo "Task#$task_id finished, data_len=" . PHP_EOL;
        var_dump($data);
    }

}