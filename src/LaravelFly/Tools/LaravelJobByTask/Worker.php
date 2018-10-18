<?php
/**
 * User: scil
 * Date: 2018/10/17
 * Time: 16:13
 */

namespace LaravelFly\Tools\LaravelJobByTask;


use Illuminate\Queue\FailingJob;
use Illuminate\Queue\WorkerOptions;

class Worker extends \Illuminate\Queue\Worker
{

    /**
     * @param TaskJobQueue $connection
     * @param TaskJob $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    protected function getNextJob($connection, $queue)
    {
        try {
            // hack  no parse string, return object
            // foreach (explode(',', $queue) as $queue) {
            if (!is_null($job = $connection->pop($queue))) {
                return $job;
            }
            // }
        } catch (Exception $e) {
            $this->exceptions->report($e);

            $this->stopWorkerIfLostConnection($e);

            $this->sleep(1);
        } catch (Throwable $e) {
            $this->exceptions->report($e = new FatalThrowableError($e));

            $this->stopWorkerIfLostConnection($e);

            $this->sleep(1);
        }
    }

    public function daemon($connectionName, $queue, WorkerOptions $options)
    {

        // todo
//        if ($this->supportsAsyncSignals()) {
//            $this->listenForSignals();
//        }

        $lastRestart = $this->getTimestampOfLastQueueRestart();

        while (true) {

            $job = $this->getNextJob(
                $this->manager->connection($connectionName), $queue
            );

            //todo
//            if ($this->supportsAsyncSignals()) {
//                $this->registerTimeoutHandler($job, $options);
//            }

            if ($job) {
                $this->runJob($job, $connectionName, $options);
            } else {
                return;
            }

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the queue should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
            $this->stopIfNecessary($options, $lastRestart);
        }
    }

    protected function stopIfNecessary(WorkerOptions $options, $lastRestart)
    {


//        if ($this->shouldQuit) {
//            $this->kill();
//        }

        if ($this->memoryExceeded($options->memory)) {
            $this->stop(12);
        }
//        elseif ($this->queueShouldRestart($lastRestart)) {
//            $this->stop();
//        }
    }

    public function stop($status = 0)
    {
        $this->events->dispatch(new \Illuminate\Queue\Events\WorkerStopping);

        // todo
        // exit($status);
    }

    // todo
    protected function failJob($connectionName, $job, $e)
    {
        return FailingJob::handle($connectionName, $job, $e);
    }
}