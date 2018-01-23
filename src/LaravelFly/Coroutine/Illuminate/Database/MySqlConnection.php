<?php
/**
 *
 *
 * Date: 2018/1/20
 * Time: 23:16
 */

namespace LaravelFly\Coroutine\Illuminate\Database;


class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings){
            if ($this->pretending()) {
                return [];
            }

            $statement = $this->preparedForSwoole($this->pdo->prepare($query));

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            return $statement->fetchAll();
        });
    }
    protected function preparedForSwoole($statement)
    {
//        $statement->setFetchMode($this->fetchMode);

        $this->event(new \Illuminate\Database\Events\StatementPrepared(
            $this, $statement
        ));

        var_dump($statement);
        return $statement;
    }

}