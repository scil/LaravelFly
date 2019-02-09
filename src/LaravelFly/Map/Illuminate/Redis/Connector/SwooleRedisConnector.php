<?php

namespace LaravelFly\Map\Illuminate\Redis\Connector;

use Illuminate\Support\Arr;
use LaravelFly\Map\Illuminate\Redis\Connection\SwooleRedisConnection;
use Swoole\Coroutine\Redis;

class SwooleRedisConnector
{

    public function connect(array $config, array $options)
    {
        $config = array_merge(
            $config, $options, Arr::pull($config, 'options', [])
        );
        $client = $this->createClient($config);

        $connection = new SwooleRedisConnection($client);
        $connection->saveConfig($config);
        return $connection;
    }

    protected function createClient(array $config)
    {
        /**
         * __construct:
         *  // from: https://wiki.swoole.com/wiki/page/762.html
         *  timeout
         *  password // 等同于auth指令
         *  database
         *
         */
        $client = new Redis($config);
        $client->connect($config['host'], $config['port'], $config['var_serialize'] ?? false);
        return $client;
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