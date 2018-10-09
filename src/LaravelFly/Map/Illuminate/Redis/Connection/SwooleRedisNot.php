<?php
/**
 * because swoole redis client's API is like PhpRedis
 *  https://wiki.swoole.com/wiki/page/590.html
 */

namespace LaravelFly\Map\Illuminate\Redis\Connection;



trait SwooleRedisNot
{

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