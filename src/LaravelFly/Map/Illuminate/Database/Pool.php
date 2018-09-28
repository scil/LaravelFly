<?php

namespace LaravelFly\Map\Illuminate\Database;

class Pool
{
    /**
     * @var \Swoole\Coroutine\Channel
     */
    protected $pool;

    /**
     * @var \Closure
     */
    protected $maker;

    public function __construct($name, DatabaseManager $db, $size = 20)
    {

        list($database, $type) = $db->parseConnName($name);

        $this->pool = new \Swoole\Coroutine\Channel($size);

        $this->maker = function () use ($db, $database, $type) {
            return $db->makeOneConn($database, $type);
        };

        for ($i = 0; $i < $size; $i++) {
            $this->put($db->makeOneConn($database, $type));
        }
    }

    function makeConn()
    {
        return $this->{'maker'}();

    }

    public function get()
    {
        return $this->pool->pop();
    }

    public function put($conn)
    {
        // pool->push must be in a coroutine
        go(function()use($conn){
            $this->pool->push($conn);
        });
    }
}