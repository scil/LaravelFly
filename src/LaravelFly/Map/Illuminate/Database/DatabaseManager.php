<?php

namespace LaravelFly\Map\Illuminate\Database;

use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager as IlluminateDatabaseManager;

class DatabaseManager extends IlluminateDatabaseManager
{
    /**
     * @var Pool[] $pools
     */
    protected $pools = [];

    /**
     *
     * [
     *      cid => [name1 => conn1, name2 => conn2 ]
     * ]
     *
     * @var array $connections
     */
    protected $connections = [];


    public function __construct($app, ConnectionFactory $factory)
    {
        parent::__construct($app, $factory);

        $this->connections[WORKER_COROUTINE_ID] = [];

        $defaultPoolsize = \LaravelFly\Fly::getServer()->getConfig('poolsize');

        foreach ($this->app['config']['database.connections'] as $name => $config) {
            $this->pools[$name] = new Pool($name, $this, $config['poolsize'] ?? $defaultPoolsize);
        }

        $event = Container::getInstance()->make('events');

        $event->listen('request.corinit', function ($cid) {
            $this->connections[$cid] = $this->connections[WORKER_COROUTINE_ID];
        });
        $event->listen('request.corunset', function ($cid) {
            $this->putBack($cid);
        });
        $event->listen('usercor.init', function ($parentId, $childId) {
            $this->connections[$childId] = $this->connections[$parentId];
        });

        $event->listen('usercor.unset', function ($childId) {
            $this->putBack($childId);

        });
        $event->listen('usercor.unset2', function ($parentId, $childId) {
            $this->connections[$parentId] = $this->connections[$childId];
            unset($this->connections[$childId]);
        });


    }

    function putBack($cid)
    {
        foreach ($this->connections[$cid] as $name => $conn) {
            // no worry about disconnected connections, because laravel will reconnect it when using it
            $this->pools[$name]->put($conn);
        }
    }

    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();

        $cid = \Swoole\Coroutine::getuid();

        if (!isset($this->connections[$cid][$name])) {

            return $this->connections[$cid][$name] = $this->pools[$name]->get();
        }

        return $this->connections[$cid][$name];

    }

    function makeOneConn($database, $type)
    {
        return $this->configure(
            $this->makeConnection($database), $type
        );
    }

    function parseConnName($name)
    {

        list($database, $type) = $this->parseConnectionName($name);

        // $name = $name ?: $database;

        return [$database, $type];
    }

    public function purge($name = null)
    {

        $name = $name ?: $this->getDefaultConnection();

        $cid = \Swoole\Coroutine::getuid();


        if (isset($this->connections[$cid][$name])) {

            // hack, put back, not disconnect
            // $this->disconnect($name);
            $this->pools[$name]->put($this->connections[$cid][$name]);

            unset($this->connections[$cid][$name]);
        }
    }

    public function disconnect($name = null)
    {
        $cid = \Swoole\Coroutine::getuid();

        if (isset($this->connections[$cid][$name = $name ?: $this->getDefaultConnection()])) {
            $this->connections[$cid][$name]->disconnect();
        }
    }

    public function reconnect($name = null)
    {
        $this->disconnect($name = $name ?: $this->getDefaultConnection());

        if (!isset($this->connections[\Swoole\Coroutine::getuid()][$name])) {
            return $this->connection($name);
        }

        return $this->refreshPdoConnections($name);
    }

    /**
     * Refresh the PDO connections on a given connection.
     *
     * @param  string $name
     * @return \Illuminate\Database\Connection
     */
    protected function refreshPdoConnections($name)
    {
        $fresh = $this->makeConnection($name);

        return $this->connections[\Swoole\Coroutine::getuid()][$name]
            ->setPdo($fresh->getPdo())
            ->setReadPdo($fresh->getReadPdo());
    }

    public function getConnections()
    {
        return $this->connections[\Swoole\Coroutine::getuid()];
    }
}