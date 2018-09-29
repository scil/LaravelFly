<?php
/**
 * User: scil
 * Date: 2018/9/29
 * Time: 9:10
 */

namespace LaravelFly\Map\Illuminate\Redis\Connector;


use Illuminate\Support\Arr;
use LaravelFly\Map\Illuminate\Redis\Connection\PhpRedisConnection;

class PhpRedisConnector extends  \Illuminate\Redis\Connectors\PhpRedisConnector
{
    public function connect(array $config, array $options)
    {

        $config = array_merge(
            $config, $options, Arr::pull($config, 'options', [])
        );

        // hack
        // disable persistent, we have got connection pool
        $config['persistent'] = false;


        // hack
        // PhpRedisConnection add new mothod reconnect() and Exception NotImplemented
        return new PhpRedisConnection($this->createClient($config));
    }

}