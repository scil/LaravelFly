<?php

namespace LaravelFly\Server\Traits;

use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Queue;
use Illuminate\Queue\WorkerOptions;
use LaravelFly\Map\IlluminateBase\Dispatcher;
use LaravelFly\Tools\LaravelJobByTask\TaskJob;
use LaravelFly\Tools\LaravelJobByTask\TaskJobServiceProvider;

trait Task
{

    /**
     * @var \LaravelFly\Tools\LaravelJobByTask\Worker
     */
    protected $taskWorker;

    /**
     * @var Queue
     */
    protected $laravelQueue;

    /**
     * @var Dispatcher
     */
    protected $laravelEvent;

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
        switch ($data['type'] ?? 'job') {
            case 'job':
//        echo "#{$server->worker_id}\tonTask: [PID={$server->worker_pid}]: task_id=$task_id, data_len=" . "." . PHP_EOL;
//        var_dump($data);

                $object = $data['value'] ?? $data;
                if (!method_exists($object, 'handle')) return;
                $this->runLaravelJob($object, $task_id, $from_id);
                break;
        }
    }

    protected function runLaravelJob($object, $task_id, $from_id)
    {

        $config = $this->app['config']["queue.connections.swoole-job"];

        $options = new WorkerOptions(
            null, $config['memory'],
            $config['timeout'], 0,
            $object->tries ?? $config['tries'], $config['force']
        );

        $delay = $this->getJobDelay($object, $config);

        $job = new TaskJob($this->app, $this->swoole, $object, $task_id, $from_id);

        if ($delay) {
            swoole_timer_after($delay * 1000, function () use ($job, $options) {

                $this->laravelQueue->pushJobObject($job);

                $this->taskWorker->daemon('swoole-job', $job, $options);
            });
            return;

        }


        $this->laravelQueue->pushJobObject($job);

        $this->taskWorker->daemon('swoole-job', $job, $options);
//        $worker->runNextJob('swoole-job', $job, $options);

    }


    /**
     * @param Application $app
     * @param \LaravelFly\Tools\LaravelJobByTask\Worker $worker
     * @param $data
     * @param int $task_id
     * @param int $from_id
     *
     * an example to test job class ProcessPodcast
     *
     * Route::get('/dd', function () {
     *      $pendingJob = ProcessPodcast::dispatch(2);
     *      $rJob= new ReflectionProperty($pendingJob,'job');
     *      $rJob->setAccessible(true);
     *      $data = $rJob->getValue($pendingJob);
     *      $app = app();
     *
     *      $taskServiceProvider = new TaskJobServiceProvider($app);
     *      $taskServiceProvider->register(true);
     *      (new \LaravelFly\Server\HttpServer())->runTaskForTest($app, $app->make('queue.worker'), $data);
     *
     *      $taskServiceProvider->restore();
     *
     *      return 3;
     * });
     *
     */
    function runTaskForTest(Application $app, \LaravelFly\Tools\LaravelJobByTask\Worker $worker, $data, $task_id = 0, $from_id = 0)
    {
        $this->app = $app;
        $this->taskWorker = $worker;
        $this->runLaravelJob($data, $task_id, $from_id);
    }


    protected function getJobDelay($job, $config)
    {

        $delay = $job->delay;
        if (null === $delay) {
            $delay = $config['delay'];
        } elseif ($delay instanceof Carbon) {
            $delay = $delay->diffInSeconds();
        }

        if ($delay <= 0) {
            $delay = null;
        }
        if ($delay >= 86400) {
            // todo
            // throw new \InvalidArgumentException('The max delay is 86400s');
            $delay = 86400;
        }

        return $delay;

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

        (new TaskJobServiceProvider($this->app))->register();

        $this->taskWorker = $this->app->make('queue.worker');
        $this->laravelQueue = $this->app->make('queue');
        $this->laravelEvent = $this->app->make('events');
        $this->listenForLaravelJobEvents();


    }

    protected function listenForLaravelJobEvents()
    {

        $this->laravelEvent->listen(JobProcessing::class, function ($event) {
            $this->writeOutput($event->job, 'starting');
        });

        $this->laravelEvent->listen(JobProcessed::class, function ($event) {
            $this->writeOutput($event->job, 'success');
        });

        $this->laravelEvent->listen(JobFailed::class, function ($event) {
            $this->writeOutput($event->job, 'failed');

            // todo
            // $this->logFailedJob($event);
        });
    }

    protected function writeOutput(TaskJob $job, $status)
    {
        switch ($status) {
            case 'starting':
                return $this->writeStatus($job, 'Processing', 'comment');
            case 'success':
                return $this->writeStatus($job, 'Processed', 'info');
            case 'failed':
                return $this->writeStatus($job, 'Failed', 'error');
        }
    }

    /**
     * Format the status output for the queue worker.
     *
     * @param  \Illuminate\Contracts\Queue\Job $job
     * @param  string $status
     * @param  string $type
     * @return void
     */
    protected function writeStatus(TaskJob $job, $status, $type)
    {
        $this->output->writeln(sprintf(
            "<{$type}>[%s][%s] %s</{$type}> %s",
            Carbon::now()->format('Y-m-d H:i:s'),
            $job->getJobId(),
            str_pad("{$status}:", 11), $job->resolveName()
        ));
    }

}