<?php

namespace LaravelFly\Server\Traits;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\WorkerOptions;
use LaravelFly\Tools\Task\SwooleTaskJob;
use LaravelFly\Tools\Task\TaskServiceProvider;

trait Task
{

    /**
     * The exception handler instance.
     *
     * @var \LaravelFly\Tools\Task\Worker
     */
    protected $taskWorker;

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
//        echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=" . "." . PHP_EOL;
//        var_dump($data);

        if (!method_exists($data, 'handle')) {
            return;
        }

        $this->runTask($data, $from_id, $data);
    }


    /**
     * @param Application $app
     * @param \LaravelFly\Tools\Task\Worker $worker
     * @param $data
     * @param int $task_id
     * @param int $from_id
     *
     * an example to test job class ProcessPodcast
     *
    Route::get('/dd', function () {
        $pendingJob = ProcessPodcast::dispatch(2);
        $rJob= new ReflectionProperty($pendingJob,'job');
        $rJob->setAccessible(true);
        $data = $rJob->getValue($pendingJob);
        $app = app();

        $taskServiceProvider = new TaskServiceProvider($app);
        $taskServiceProvider->register(true);
        (new \LaravelFly\Server\HttpServer())->runTaskForTest($app, $app->make('queue.worker'), $data);

        $taskServiceProvider->restore();

        return 3;
    });
     *
     */
    function runTaskForTest(Application $app, \LaravelFly\Tools\Task\Worker $worker, $data, $task_id = 0, $from_id = 0)
    {
        $this->app = $app;
        $this->taskWorker = $worker;
        $this->runTask($data, $task_id, $from_id);

    }

    protected function runTask($data, $task_id, $from_id)
    {

        $job = new SwooleTaskJob($this->app, $this->swoole, $data, $task_id, $from_id);

        $config = $this->app['config']["queue.connections.swoole-job"];

        $options = new WorkerOptions(
            $data->delay ?? $config['delay'], $config['memory'],
            $config['timeout'], 0,
            $data->tries ?? $config['tries'], $config['force']
        );

        $this->app->make('queue')->pushJobObject($job);

        $this->taskWorker->daemon('swoole-job', $job, $options);
//        $worker->runNextJob('swoole-job', $job, $options);


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

        (new TaskServiceProvider($this->app))->register();

        $this->taskWorker = $this->app->make('queue.worker');


    }
}