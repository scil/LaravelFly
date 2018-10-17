<?php

namespace LaravelFly\Tools\Task;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;
use LaravelFly\Tools\Task\Connectors\SwooleTaskConnector;


/**
 * @codeCoverageIgnore
 */
class TaskServiceProvider extends ServiceProvider
{

    /**
     * @var string
     */
    protected $oldDefault;

    /**
     * @var \Illuminate\Queue\Worker
     */
    protected $oldWorker;

    /**
     * @param bool $backup only for debugging in fpm
     */
    public function register($backup = false)
    {
        if ($backup) {
            $this->oldDefault = $this->app['config']['queue.default'];
            $this->oldWorker = $this->app->make('queue.worker');
        }

        $this->app['queue']->addConnector('swoole-task', function () {
            return new SwooleTaskConnector(
            // null? allow debugging in fpm
                method_exists($this->app, 'getSwoole') ? $this->app->getSwoole() : null
            );
        });


        $this->app['config']['queue.default'] = 'swoole-job';

        $this->app['config']["queue.connections.swoole-job"] =
            $this->app['config']->get("laravelfly.swoole-job", []) + [
                'driver' => 'swoole-task',
                'delay' => 0,
                'force' => true,
                'memory' => 128,
                'timeout' => 60,
                'tries' => 0,
            ];


        // from: Illuminate\Queue\QueueServiceProvider::registerWorker
        $this->app->singleton('queue.worker', function () {
            return $this->taskWorker = new \LaravelFly\Tools\Task\Worker(
                $this->app['queue'], $this->app['events'], $this->app[ExceptionHandler::class]
            );
        });
    }

    /**
     * @param bool $full whether restore default queue connection
     */
    function restore($full = false)
    {
        if ($this->oldWorker) {

            $this->app['config']['queue.default'] = $full ? $this->oldDefault : 'sync';

            $this->app->instance('queue.worker', $this->oldWorker);
        }
    }

}
