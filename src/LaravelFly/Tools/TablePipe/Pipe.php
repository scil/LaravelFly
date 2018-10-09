<?php
namespace LaravelFly\Tools\TablePipe;


use LaravelFly\Tools\SessionTable;

abstract class Pipe implements PipeInterface
{
    /**
     * @var SessionTable
     */
    protected $table;

}