<?php
/**
 * Created by PhpStorm.
 * User: iv
 * Date: 2018/1/20
 * Time: 22:39
 */

namespace LaravelFly\Coroutine\Illuminate\Database;


use LaravelFly\Coroutine\Pool\MySql;

class MySqlConnector extends \Illuminate\Database\Connectors\MySqlConnector
{

    function createConnection($dsn, array $config, array $options)
    {
        list($username, $password) = [
            $config['username'] ?? null, $config['password'] ?? null,
        ];

        try {
            MySql::set('default',$config);

            return MySql::get('default');
        } catch (Exception $e) {
            return $this->tryAgainIfCausedByLostConnection(
                $e, $dsn, $username, $password, $options
            );
        }
    }
}