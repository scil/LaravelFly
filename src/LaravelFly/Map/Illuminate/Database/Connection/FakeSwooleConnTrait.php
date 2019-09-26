<?php

namespace LaravelFly\Map\Illuminate\Database\Connection;

use Illuminate\Support\Arr;
use LaravelFly\Map\Illuminate\Database\PDO\SwoolePDO;

/**
 * used by Smf. For example:
 *      $connection->connected;
 *      $connection->close();
 * vendor/open-smf/connection-pool/src/Connectors/CoroutineMySQLConnector.php
 */
trait FakeSwooleConnTrait
{

    /**
     *
     * @var \Swoole\Coroutine\Redis | \Swoole\Coroutine\MySQL
     */
    protected $swooleConnection;


    /**
     * save config, only for reconnect
     * @param $config
     */
    public function fake($swooleConnection)
    {
        $this->swooleConnection = $swooleConnection;
    }

    public function __get($key){
        return $this->swooleConnection->{$key};
    }

    public function __call($method, $parameters){
        return $this->swooleConnection->$method(...$parameters);
    }
}