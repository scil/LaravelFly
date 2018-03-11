<?php

namespace LaravelFly\Map\Illuminate\Database;

use Illuminate\Database\Connectors\PostgresConnector;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\Connectors\SqlServerConnector;
use PDOException;
use InvalidArgumentException;
use Illuminate\Database\Connection;
//use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Arr;

;

use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;

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
                if ($config['coroutine'] ?? false) {
                    return new Connectors\MySqlConnector;
                }
                return new \Illuminate\Database\Connectors\MySqlConnector;
            case 'pgsql':
                return new PostgresConnector;
            case 'sqlite':
                return new SQLiteConnector;
            case 'sqlsrv':
                return new SqlServerConnector;
        }

        throw new InvalidArgumentException("Unsupported driver [{$config['driver']}]");
    }

    public function make(array $config, $name = null)
    {

        $config = $this->parseconfig($config, $name);

        if ($config['coroutine'] ?? false) {
            /**
             *
             * laravel uses Closure, but laravelfly can not. because swoole coroutine can't used in closure.
             * I have tried to use closure to reture an object wrapping a swoole myssql coroutine client
             * then i enter blackhole, no response, no error msg.... nothing at all forever.
             *
             * this put you into blackhole:
             * $connector = $this->createSwooleResolver($config);
             */
            $connector= $this->createConnector($config)->connect($config);
            return $this->createSwooleCortoutineConnection(
                $config['driver'],$connector,$config['database'], $config['prefix'], $config
            );
        }

        if (isset($config['read'])) {
            return $this->createReadWriteConnection($config);
        }
        return $this->createSingleConnection($config);
    }

    /**
     * follow createPdoResolver() or createPdoResolverWithHosts(), they return a closure
     * but this is not good for swoole coroutine. it leads to blackhole
     */
    protected function createSwooleResolver($config)
    {
        return function () use ($config) {
            return $this->createConnector($config)->connect($config);
        };
    }

    protected function createSwooleCortoutineConnection($driver, $connector, $database, $prefix = '', array $config = [])
    {

        if ($resolver = Connection::getResolver($driver)) {
            return $resolver($connector, $database, $prefix, $config);
        }

        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connector, $database, $prefix, $config);
        }

        throw new InvalidArgumentException("Unsupported driver [$driver]");
    }
}