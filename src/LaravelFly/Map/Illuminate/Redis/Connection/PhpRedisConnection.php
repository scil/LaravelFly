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

}
