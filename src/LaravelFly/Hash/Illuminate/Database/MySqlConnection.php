<?php
/**
 *
 *
 * Date: 2018/1/20
 * Time: 23:16
 */

namespace LaravelFly\Hash\Illuminate\Database;


class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    public function select($query, $bindings = [], $useReadPdo = true)
    {

        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $statement = $this->preparedForSwoole($this->pdo->prepare($query));

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            return $statement->fetchAll();

        });
    }

    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and then we'll use PDO to fetch the affected.
            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            $this->recordsHaveBeenModified(
                ($count = $statement->rowCount()) > 0
            );

            return $count;
        });
    }

    protected function preparedForSwoole($statement)
    {
        //todo
//        $statement->setFetchMode($this->fetchMode);

        $this->event(new \Illuminate\Database\Events\StatementPrepared(
            $this, $statement
        ));

        return $statement;
    }

}