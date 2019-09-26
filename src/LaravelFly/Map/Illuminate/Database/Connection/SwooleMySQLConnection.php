<?php

namespace LaravelFly\Map\Illuminate\Database\Connection;

use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Database\MySqlConnection;
use LaravelFly\Map\Illuminate\Database\PDO\SwoolePDO;

class SwooleMySQLConnection extends MySqlConnection
{
    use FakeSwooleConnTrait;
    /**
     * The active swoole mysql connection.
     *
     * @var SwoolePDO  |\Closure
     */
    protected $pdo;

    /**
     * The active swoole mysql used for reads.
     *
     * @var SwoolePDO |\Closure
     */
    protected $readPdo;


    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo,$database,$tablePrefix,$config);

        $this->swooleConnection = $this->getPdo()->getSwooleConnection();
    }

    public function getDriverName()
    {
        return 'Swoole Coroutine MySQL';
    }

    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, \Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious()) || Str::contains($e->getMessage(), ['is closed', 'is not established'])) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }


    public function getClient(){
        return $this->getPdo();
    }
}