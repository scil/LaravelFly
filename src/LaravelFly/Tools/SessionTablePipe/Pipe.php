<?php
namespace LaravelFly\Tools\SessionTablePipe;


use LaravelFly\Tools\SessionTable;

abstract class Pipe implements PipeInterface
{
    /**
     * @var SessionTable
     */
    protected $table;

}