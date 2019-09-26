<?php

namespace LaravelFly\Map\Illuminate\Database\Pool;

use LaravelFly\Map\Illuminate\Database\DatabaseManager;
use LaravelFly\Map\Illuminate\Redis\RedisManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SimplePool
{
    use Pool {
        initPool as _initPool;
    }
    /**
     * @var \Swoole\Coroutine\Channel
     */
    protected $pool;

    protected $size;

    public function __construct(array $poolConfig, $connector, array $connectionConfig)
    {
        $this->size = $poolConfig['size'] ?? 20;
    }

    function initPool($name, $db,EventDispatcher $dispatcher )
    {
        $this->_initPool($name, $db,$dispatcher);


        $this->pool = new \Swoole\Coroutine\Channel($this->size);
        for ($i = 0; $i < $this->size; $i++) {
            $this->return($this->createConnection());
        }

    }

    function createConnection()
    {
        return $this->dbmgr->makeConnectionForPool($this->name);

    }

    public function borrow()
    {
        return $this->pool->pop();
    }

    public function return($conn)
    {
        $this->pool->push($conn);
    }

    public function len()
    {
        return $this->pool->length();
    }
}