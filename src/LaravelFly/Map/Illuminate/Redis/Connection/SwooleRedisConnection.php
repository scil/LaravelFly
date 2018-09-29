<?php

namespace LaravelFly\Map\Illuminate\Redis\Connection;


use Illuminate\Redis\Connections\PhpRedisConnection;

class SwooleRedisConnection extends PhpRedisConnection implements EnsureConnected
{
    /**
     *
     * @var \Swoole\Coroutine\Redis
     */
    protected $client;

    protected $config;


    /**
     * save config, only for reconnect
     * @param $config
     */
    public function saveConfig(&$config)
    {
        $this->config = $config;
        $this->connected = true;
    }

    public function disconnect()
    {
        $this->client->close();
    }

    public function ensureConnected()
    {
        if (!$this->client->connected){
            $config = $this->config;

            $this->client->connect($config['host'], $config['port'], $config['var_serialize'] ?? false);

            //todo
            // assert($this->client->connected === true);

        }
    }

    public function reconnect()
    {
        if ($this->client->connected)
            $this->disconnect();

        $config = $this->config;

        $this->client->connect($config['host'], $config['port'], $config['var_serialize'] ?? false);


    }
}