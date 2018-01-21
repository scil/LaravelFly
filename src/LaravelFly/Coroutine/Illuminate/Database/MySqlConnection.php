<?php
/**
 * Created by PhpStorm.
 * User: iv
 * Date: 2018/1/20
 * Time: 23:16
 */

namespace LaravelFly\Coroutine\Illuminate\Database;


class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    public function select($query, $bindings = [], $useReadPdo = true)
    {

        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }

            //todo
            //laravel-swoole-cp ： https://github.com/breeze2/laravel-swoole-cp/blob/master/vendor/laravel/framework/src/Illuminate/Database/MySqlSwooleProxyConnection.php
            $statement = $this->prepared($this->getPdoForSelect($useReadPdo)
                ->prepare($query));

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            return $statement->fetchAll();
        });
    }

}