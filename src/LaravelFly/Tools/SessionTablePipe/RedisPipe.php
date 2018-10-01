<?php

namespace LaravelFly\Tools\SessionTablePipe;

use LaravelFly\Tools\SessionTable;

class RedisPipe extends Pipe
{
    protected $table;
    protected $handler;

    protected $prefix;
    /**
     * @var \Illuminate\Foundation\Application|\Illuminate\Redis\Connections\PredisConnection|mixed
     */
    protected $redis;

    function __construct(SessionTable $table)
    {
        $this->table = $table;

        $this->redis = app('redis')->connection(config('session.connection'));
        $this->prefix = config('cache.prefix');
    }

    function restore()
    {
        // TODO: Implement export() method.

        return $this->redis->get($this->prefix.$sessionId) ?: '';
    }

    function dump()
    {
        foreach ($this->table as $key => $row) {
            /**
             * @var \swoole_table_row $row
             */
            $lasttime = $row['last_activity'];
            $used = round(time() / 60) - $lasttime;
            $remained = $this->handler->minutes - $used;
            if ($remained > 0) {

                $result = $this->redis->setEx($this->prefix . $key, $remained, $row['payload']);

            }

        }
    }
}