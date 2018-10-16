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
        echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=" . "." . PHP_EOL;
//        var_dump($data);
        if (method_exists($data, 'handle')) {
            $data->handle();
        }
    }

    function onFinish($server, $task_id, $data)
    {
        echo "Task#$task_id finished, data_len=" . PHP_EOL;
        var_dump($data);
    }

    /**
     * create an app and bootstrap all service providers
     *
     * content mainly from method startLaravel(), plus bootInRequest()
     *
     * @param \swoole_server $server
     * @param int $worker_id
     */
    public function onTaskWorkerStart(\swoole_server $server, int $worker_id)
    {
        if (!$this->getConfig('early_laravel')) {

            $app = $this->_makeLaravelApp();

            $this->_makeRequest();

            if (LARAVELFLY_SERVICES['request']) {
                /**
                 * otherwise error produced by something like this
                 *      app('request')->header('x-pjax', ''));
                 * because in __construct , LaravelFly\Map\IlluminateBase\Request give null to its props like 'header' 'query'.
                 */
                $app->make('request')->nullPropToObject();
            }

        }


        try {

            if (!$this->getConfig('early_laravel')) {
                $this->kernel->bootstrap();
            }
            $app->bootInRequest();

        } catch (\Swoole\ExitException $e) {

            var_dump($e->getTrace()[0]);
            echo "\n[FLY EXIT IN TASK] exit() or die() executes.\n";
            // $server && $server->shutdown();

        } catch (\Throwable $e) {

            echo $e->getTraceAsString();
            $msg = $e->getMessage();
            echo "\n[LARAVEL BOOTSTRAP ERROR IN TASK] $msg\n";
            // $server && $server->shutdown();

        }


    }
}