<?php

namespace LaravelFly\Coroutine\Illuminate\Database;

use PDOException;
use InvalidArgumentException;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;

class ConnectionFactory extends \Illuminate\Database\Connectors\ConnectionFactory
{
    public function createConnector(array $config)
    {
        if (!isset($config['driver'])) {
            throw new InvalidArgumentException('A driver must be specified.');
        }

        if ($this->container->bound($key = "db.connector.{$config['driver']}")) {
            return $this->container->make($key);
        }

        switch ($config['driver']) {
            case 'mysql':
                return new MySqlConnector;
            case 'pgsql':
            case 'sqlite':
            case 'sqlsrv':
        }

        throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]");
    }

    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connection, $database, $prefix, $config);
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
            case 'pgsql':
            case 'sqlite':
            case 'sqlsrv':
        }

        throw new InvalidArgumentException("Unsupported driver [$driver]");
    }
}