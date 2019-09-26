<?php

namespace LaravelFly\Map\Illuminate\Redis\Connector;

use Illuminate\Support\Arr;
use LaravelFly\Map\Illuminate\Database\Connectors\RedisConnectorTrait;
use LaravelFly\Map\Illuminate\Redis\Connection\SwooleRedisConnection;
use Swoole\Coroutine\Redis;

class SwooleRedisConnector
{
    use RedisConnectorTrait;

    public function connect(array $config, array $options)
    {
        $options = array_merge(
             $options, Arr::pull($config, 'options', [])
        );
        $client =  $this->_connect($config,$options);

        $connection = new SwooleRedisConnection($client);
        $connection->saveConfig($config);
        return $connection;
    }


    // todo
    public function connectToCluster(array $config, array $clusterOptions, array $options)
    {
        $options = array_merge($options, $clusterOptions, Arr::pull($config, 'options', []));

        return new PhpRedisClusterConnection($this->createRedisClusterInstance(
            array_map([$this, 'buildClusterConnectionString'], $config), $options
        ));
    }

    protected function buildClusterConnectionString(array $server)
    {
        return $server['host'] . ':' . $server['port'] . '?' . http_build_query(Arr::only($server, [
                'database', 'password', 'timeout',
            ]));
    }


    // todo
    protected function createRedisClusterInstance(array $servers, array $options)
    {
        return new RedisCluster(
            null,
            array_values($servers),
            $options['timeout'] ?? 0,
            $options['read_timeout'] ?? 0,
            isset($options['persistent']) && $options['persistent']
        );
    }

}