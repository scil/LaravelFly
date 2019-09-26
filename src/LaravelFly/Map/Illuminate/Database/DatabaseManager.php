<?php

namespace LaravelFly\Map\Illuminate\Database;

class DatabaseManager extends \Illuminate\Database\DatabaseManager
{
    use ConnectionsTrait;

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

        $this->initConnections($this->app['config']['database.connections']);

    }

    /**
     * Laravel officail API. only one conn for a $name
     * The conn in current coroutine will go back to pool in method putBack
     *
     * @param null $name
     * @return \Illuminate\Database\Connection
     */
    public function connection($name = null)
    {
        $name = $name ?: $this->getDefaultConnection();

        $cid = \Swoole\Coroutine::getuid();

        if (!isset($this->connections[$cid][$name])) {

            return $this->connections[$cid][$name] = $this->pools[$name]->borrow();
        }

        return $this->connections[$cid][$name];

    }

    /**
     * play the role of parent::connection which contains:
     *
     *      [$database, $type] = $this->parseConnectionName($name);
     *
     *      $this->connections[$name] = $this->configure(
     *          $this->makeConnection($database), $type
     *      );
     *
     * @return mixed
     */
    function makeConnectionForPool($name)
    {
        list($database, $type) = $this->parseConnectionName($name);

        // $name = $name ?: $database;
        return $this->configure(
            $this->makeConnection($database), $type
        );
    }

    public function purge($name = null)
    {

        $name = $name ?: $this->getDefaultConnection();

        $cid = \Swoole\Coroutine::getuid();


        if (isset($this->connections[$cid][$name])) {

            // hack, put back, not disconnect
            // $this->disconnect($name);
            $this->pools[$name]->return($this->connections[$cid][$name]);

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