<?php


namespace LaravelFly\Coroutine\Pool;


class Redis extends Pool
{
    protected static function create($name):\Swoole\Coroutine\Redis{
        $redis = new \Swoole\Coroutine\Redis();
        return $redis->connect('127.0.0.1', 6379);
    }

}