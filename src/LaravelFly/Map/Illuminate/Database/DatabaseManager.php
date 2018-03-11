<?php
/**
 *
 *
 * Date: 2018/1/21
 * Time: 19:18
 */

namespace LaravelFly\Map\Illuminate\Database;


class DatabaseManager extends \Illuminate\Database\DatabaseManager
{
    protected $pool;
    protected $nameConfig;

    public function connection($name = null)
    {
        list($database, $type) = $this->parseConnectionName($name);

        $name = $name ?: $database;

        $config = $this->getConfigForName($name);

        if ($config['coroutine'] ?? false) {
            return $this->getFromPool($name);
        }

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->configure(
                $this->makeConnection($database), $type
            );
        }

        return $this->connections[$name];
    }

    protected function getConfigForName($name)
    {

        if (!isset($this->nameConfig[$name])) {
            $this->nameConfig[$name] = $this->configuration($name);
            $this->pool[$name] = new \SplQueue;
        }
        return $this->nameConfig[$name];
    }

    protected function getFromPool($name)
    {
        $pool = $this->pool[$name];
        if (!$pool->isEmpty()) {
            return $pool->pop();
        }

        return $this->createForPool($name);

    }

    protected function createForPool($name)
    {
        return $this->configure(
            $this->makeConnectionWithConfig($name, $this->nameConfig[$name]),
            null
        );
    }

    /**
     *  the $config is useful before {@link makeConnection()},
     *  so making it in {@link connection()} and create this new function. to replace makeConnection()
     *
     * @param $name
     * @param $config
     * @return \Illuminate\Database\Connection|mixed
     */
    protected function makeConnectionWithConfig($name, $config)
    {

        // First we will check by the connection name to see if an extension has been
        // registered specifically for that connection. If it has we will call the
        // Closure and pass it the config allowing it to resolve the connection.
        if (isset($this->extensions[$name])) {
            return call_user_func($this->extensions[$name], $config, $name);
        }

        // Next we will check to see if an extension has been registered for a driver
        // and will call the Closure if so, which allows us to have a more generic
        // resolver for the drivers themselves which applies to all connections.
        if (isset($this->extensions[$driver = $config['driver']])) {
            return call_user_func($this->extensions[$driver], $config, $name);
        }

        return $this->factory->make($config, $name);
    }
}