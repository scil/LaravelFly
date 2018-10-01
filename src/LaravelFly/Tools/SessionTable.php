<?php

namespace LaravelFly\Tools;


use LaravelFly\Tools\SessionTablePipe\PipeInterface;
use Swoole\Async;

class SessionTable extends Table
{
    /**
     * @var PipeInterface $pipe
     */
    protected $pipe;

    /**
     * The number of minutes the session should be valid.
     *
     * @var int
     */
    var $minutes;

    function init(PipeInterface $pipe, int $min = 120)
    {
        $this->pipe = $pipe;
        $this->minutes = $min;
    }

    public function restore()
    {
        $this->pipe->restore();
    }

    public function dump()
    {
        $this->pipe->dump();
    }

    function expired($lasttime)
    {
        return round(time() / 60) - $lasttime > $this->minutes;
    }
    function valid($lasttime)
    {
        return round(time() / 60) - $lasttime < $this->minutes;
    }

    public function load(array $data)
    {
        foreach ($data as $key => $row) {
            if ($this->valid($row['last_activity']))
                $this->set($key, $row);
        }
    }

}