<?php

namespace LaravelFly\Map\Illuminate\Database;

use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Database\MySqlConnection;
use LaravelFly\Map\Illuminate\Database\PDO\SwoolePDO;

class SwooleMySQLConnection extends MySqlConnection
{
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

    /**
     * used by Smf.
     * vendor/open-smf/connection-pool/src/Connectors/CoroutineMySQLConnector.php:21
     */
    public function close(){
        $this->getPdo()->close();
    }
}