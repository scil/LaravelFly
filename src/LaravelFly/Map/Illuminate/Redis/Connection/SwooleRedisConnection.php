<?php

namespace LaravelFly\Map\Illuminate\Redis\Connection;


use Illuminate\Redis\Connections\PhpRedisConnection;
use LaravelFly\Map\Illuminate\Database\Connection\FakeSwooleConnTrait;

class SwooleRedisConnection extends PhpRedisConnection implements EnsureConnected
{
    use FakeSwooleConnTrait;
    use SwooleRedisNot;

    public function __construct($client)
    {
        parent::__construct($client);

        $this->swooleConnection = $client;
    }

    public function disconnect()
    {
        $this->swooleConnection->close();
    }

    public function ensureConnected()
    {
        if (!$this->swooleConnection->connected){
            $config = $this->config;

            $this->swooleConnection->connect($config['host'], $config['port'], $config['var_serialize'] ?? false);

            //todo
            // assert($this->client->connected === true);

        }
    }

    public function reconnect()
    {
        if ($this->swooleConnection->connected)
            $this->disconnect();

        $config = $this->config;

        $this->swooleConnection->connect($config['host'], $config['port'], $config['var_serialize'] ?? false);


    }
}