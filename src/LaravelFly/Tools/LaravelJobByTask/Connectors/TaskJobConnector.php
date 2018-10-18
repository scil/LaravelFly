<?php

namespace LaravelFly\Tools\LaravelJobByTask\Connectors;

use LaravelFly\Tools\LaravelJobByTask\TaskJobQueue;
use Illuminate\Queue\Connectors\ConnectorInterface;

class TaskJobConnector implements ConnectorInterface
{
    /**
     * Swoole Server Instance
     *
     * @var \Swoole\Http\Server
     */
    protected $swoole;

     /**
     * Create a new Swoole Async task connector instance.
     *
     * @param  \Swoole\Http\Server $swoole
     * @return void
     */
    public function __construct($swoole)
    {
        $this->swoole = $swoole;
    }

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new TaskJobQueue($this->swoole);
    }
}
