<?php

namespace LaravelFly\Map\Illuminate\Database\Connectors;

use Illuminate\Support\Arr;
use LaravelFly\Map\Illuminate\Database\PDO\SwoolePDO;
use LaravelFly\Map\Illuminate\Redis\Connection\SwooleRedisConnection;
use Swoole\Coroutine\Redis;

trait RedisConnectorTrait
{

    /**
     * @param array $config
     * @return SwooleRedisConnection
     */
    public function _connect(array $config, $options)
    {
        /**
         * __construct:
         *  // from: https://wiki.swoole.com/wiki/page/762.html
         *  timeout
         *  password // 等同于auth指令
         *  database
         *
         */
        $swooleConn = new Redis($options);
        $swooleConn->connect($config['host'], $config['port'], $config['var_serialize'] ?? false);

        $conn = new SwooleRedisConnection($swooleConn);
        return $conn;
    }


}