<?php

namespace LaravelFly\Map\Illuminate\Redis\Connection;



class PhpRedisConnection extends \Illuminate\Redis\Connections\PhpRedisConnection implements EnsureConnected
{
    public function disconnect()
    {
        // todo
        $this->client->close();
    }

    public function ensureConnected()
    {
        // todo test
        // phpredis will attempt to reconnect so you can actually kill your own connection but may not notice losing it!
        // https://github.com/phpredis/phpredis
    }

    function scan($cursor, array $options = null)
    {
        throw new NotImplemented(__METHOD__);
    }

    function sort($key, array $options = null)
    {
        throw new NotImplemented(__METHOD__);
    }

    function object($subcommand, $key)
    {
        throw new NotImplemented(__METHOD__);
    }

    public function migrate($host, $port, $key, $db, $timeout, $copy = false, $replace = false)
    {
        throw new NotImplemented(__METHOD__);
    }

    function hscan($key, $cursor, array $options = null)
    {
        throw new NotImplemented(__METHOD__);
    }

    function sscan($key, $cursor, array $options = null)
    {
        throw new NotImplemented(__METHOD__);
    }

    function zscan($key, $cursor, array $options = null)
    {
        throw new NotImplemented(__METHOD__);
    }
}

class NotImplemented extends \RuntimeException
{

}