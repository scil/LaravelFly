<?php
namespace LaravelFly\Tools\LaravelJobByTask;

use Illuminate\Queue\Jobs\Job as Base;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;

class TaskJob extends Base implements JobContract
{
    /**
     * The Swoole Server instance.
     *
     * @var \Swoole\Http\Server
     */
    protected $swoole;

    /**
     * The Swoole async job raw payload.
     *
     * @var array
     */
    protected $job;

    protected $jobObject;

    /**
     * The Task id
     *
     * @var int
     */
    protected $taskId;

    /**
     * The src worker Id
     *
     * @var int
     */
    protected $srcWrokerId;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Swoole\Http\Server  $swoole
     * @param  string  $job
     * @param  int  $taskId
     * @param  int  $srcWorkerId
     * @return void
     */
    public function __construct(Container $container, $swoole, $job, $taskId, $srcWrokerId)
    {
        $this->container = $container;
        $this->swoole = $swoole;
        $this->jobObject = $job;
        $this->taskId = $taskId;
        $this->srcWorkderId = $srcWrokerId;
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {

        $this->jobObject->handle();

        // todo
        return;
            parent::fire();
    }

    /**
     * Get the number of times the job has been attempted.
     * @return int
     */
    public function attempts()
    {
        return ($this->job['attempts'] ?? null) + 1;
    }

    /**
     * Get the raw body string for the job.
     * @return string
     */
    public function getRawBody()
    {
        return $this->job;
    }


    /**
     * Get the job identifier.
     * @return string
     */
    public function getJobId()
    {
        return $this->taskId;
    }

    public function resolveName()
    {
        return get_class($this->jobObject);
    }
}
