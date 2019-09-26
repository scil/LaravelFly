<?php
/**
 * User: scil
 * Date: 2019/9/25
 * Time: 23:13
 */

namespace LaravelFly\Map\Illuminate\Database\Connectors;

use Smf\ConnectionPool\Connectors\CoroutineRedisConnector;

class SmfRedisConnector extends  CoroutineRedisConnector
{
    use RedisConnectorTrait;

    public function connect(array $config)
    {
        return $this->_connect($config,$config['options']??[]);

    }
}