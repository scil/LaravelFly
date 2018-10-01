<?php
/**
 * User: scil
 * Date: 2018/8/20
 * Time: 10:15
 */

namespace LaravelFly\Server\Traits;

use LaravelFly\Tools\SessionTable;
use swoole_atomic;

trait ShareTable
{
    /**
     * @var \swoole_table[] save shared actomic info across processes
     */
    var $tableMemory = [];

    public function getTableMemory($name): \swoole_table
    {
        return $this->tableMemory[$name];
    }

    public function newTableMemory(string $name, int $size, array $columns, $class = \swoole_table::class): \swoole_table
    {

        $table = new $class($size);

        foreach ($columns as $colName => $config) {
            $table->column($colName, $config[0], $config[1]);
        }

        $table->create();

        $this->tableMemory[$name] = $table;

        $this->{$name . 'Table'} = $table;

        $memory = $table->getMemorySize();
        $memory = round($memory / 1024 / 1024, 2);

        $this->echo("table $name created. row: $size; memory: {$memory}M");

        return $table;
    }

    function dumpSessionTable()
    {

    }

    function restoreSessionTable()
    {

    }

}