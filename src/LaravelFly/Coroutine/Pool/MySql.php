<?php
/**
 * Created by PhpStorm.
 * User: iv
 * Date: 2018/1/20
 * Time: 23:27
 */

namespace LaravelFly\Coroutine\Pool;


class MySql extends Pool
{
    protected static function create($name):\Swoole\Coroutine\MySQL {
        $swoole_mysql = new \Swoole\Coroutine\MySQL();
        $swoole_mysql->connect(static::$config[$name]);
        return  $swoole_mysql;
    }

}