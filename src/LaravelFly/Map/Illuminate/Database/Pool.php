<?php

namespace LaravelFly\Map\Illuminate\Database;

use LaravelFly\Map\Illuminate\Redis\RedisManager;

class Pool
{
    /**
     * @var \Swoole\Coroutine\Channel
     */
    protected $pool;

    /**
     * @var DatabaseManager|RedisManager
     */
    protected $db;

    /**
     * @var string $name
     */
    protected $name;

    public function __construct($name, $db, $size = 20)
    {
        $this->name = $name;

        assert(method_exists($db, 'makeOneConn'));

        /**
         * @var DatabaseManager| RedisManager $db
         */
        $this->db = $db;

        $this->pool = new \Swoole\Coroutine\Channel($size);

        for ($i = 0; $i < $size; $i++) {
            $this->put($this->makeConn());
        }
    }

    function makeConn()
    {
        return $this->db->makeOneConn($this->name);

    }

    public function get()
    {
        return $this->pool->pop();
    }

    public function put($conn)
    {
        $this->pool->push($conn);
    }

    public function len()
    {
        return $this->pool->length();
    }
}