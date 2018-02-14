<?php

namespace LaravelFly\Dict\Illuminate\Database\FakePDO;
use Mockery\Exception;

/**
 * a layer between swoole coroutine mysql and PDO
 * using PDO API
 *
 * @package LaravelFly\Dict\Illuminate\Database
 */
class SwooleCoroutineMySQL
{
    protected $swoole;

    function __construct($config)
    {
        $this->swoole = new \Swoole\Coroutine\MySQL();
        $this->swoole->connect($config);
        return $this;
    }

    function prepare($query)
    {
        if (false === $r = $this->swoole->prepare($query)) {
            throw new \Exception($this->swoole->errno. ': '. $this->swoole->error);
        }
        return new SwooleCoroutineMySQLStatement($r,$this);
    }

    function exec($query)
    {
        return $this->swoole->query($query);
    }
}

class SwooleCoroutineMySQLStatement
{
    var $binded = [];
    var $stmt;
    var $r = [];

    function __construct(\Swoole\Coroutine\MySQL\Statement $statement, $swoole)
    {
        $this->stmt = $statement;
        $this->swoole=$swoole;
    }

    function bindValue($index, $value)
    {
        $this->binded[] = $value;
    }

    function execute()
    {
        return $this->r = $this->stmt->execute($this->binded);
    }

    function fetchAll()
    {
        if (false === $this->r) {
            throw new \Exception($this->swoole->errno. ':'. $this->swoole->error);
        }

        // LaravelFly uses PDO::FETCH_OBJ
        return array_map(function ($row){ return (object)$row; },$this->r);

    }

    function rowCount()
    {
        return $this->stmt->affected_rows;
    }
}