<?php
/**
 * User: scil
 * Date: 2019/9/25
 * Time: 23:13
 */

namespace LaravelFly\Map\Illuminate\Database\Connectors;

use LaravelFly\Map\Illuminate\Redis\Connection\SwooleRedisConnection;
use Smf\ConnectionPool\Connectors\CoroutineRedisConnector;

class RedisSmfConnector extends  CoroutineRedisConnector
{
    use RedisConnectorTrait;

    public function connect(array $config)
    {
        return $this->_connect($config,$config['options']??[]);

    }
    public function validate($connection): bool
    {
        return $connection instanceof SwooleRedisConnection;
    }
}