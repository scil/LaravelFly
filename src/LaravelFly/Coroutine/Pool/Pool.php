<?php


namespace LaravelFly\Coroutine\Pool;


abstract class Pool
{
    protected static $config;
    protected static $pool;

    static function set(string $name, $options, $max=100)
    {
        static::$config[$name] = $options;
        static::$pool[$name] = new \SplQueue;
    }

    static function get($name)
    {
        $pool = static::$pool[$name];
        if (count($pool) > 0) {
            return $pool->pop();
        }

        return static::create($name);
    }
    function put($name,$conn)
    {
        static::$pool[$name]->push($conn);
    }
    protected static function create($name){

    }

}