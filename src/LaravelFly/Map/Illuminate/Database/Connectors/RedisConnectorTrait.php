<?php

namespace LaravelFly\Map\Illuminate\Database\Connectors;

use Illuminate\Support\Arr;
use LaravelFly\Map\Illuminate\Database\PDO\SwoolePDO;
use Swoole\Coroutine\Redis;

trait RedisConnectorTrait
{

    /**
     * @param array $config
     * @return Redis
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
        $connection = new Redis($options);
        $connection->connect($config['host'], $config['port'], $config['var_serialize'] ?? false);

        return $connection;
    }


}