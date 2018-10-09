<?php
namespace LaravelFly\Tools;


use LaravelFly\Tools\TablePipe\PipeInterface;

class Table extends \swoole_table
{
    /**
     * @var PipeInterface $pipe
     */
    protected $pipe;

    public function restore()
    {
        $this->pipe && $this->pipe->restore();
    }

    public function dump()
    {
        $this->pipe && $this->pipe->dump();
    }


}