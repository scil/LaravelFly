<?php

namespace LaravelFly\Map\Illuminate\Database;

use function Couchbase\defaultDecoder;
use Illuminate\Container\Container;
use LaravelFly\Map\Illuminate\Database\Connectors\MySQLSmfConnector;
use LaravelFly\Map\Illuminate\Database\Connectors\RedisSmfConnector;
use LaravelFly\Map\Illuminate\Database\Pool\SimplePool;
use LaravelFly\Map\Illuminate\Database\Pool\Pool;
use LaravelFly\Map\Illuminate\Database\Pool\SmfPool;

trait ConnectionsTrait
{
    /**
     * @var Pool[] $pools
     */
    protected $pools = [];

    function getPool($name,$driver, $config, $dispatcher)
    {
        $poolConfig = $config['pool'] ?? [];
        $pool = null;

        switch ($poolConfig['class'] ?? null) {

            case  'LaravelFly\Map\Illuminate\Database\Pool\SmfPool':

                switch ($config['driver']??$driver) {
                    case 'mysql':
                        $connector = new MySQLSmfConnector();
                        break;
                    case 'redis':
                        $connector = new RedisSmfConnector();
                        break;
                }

                $pool = new SmfPool($poolConfig, $connector, $config);
                break;

        }

        if ($pool === null) {
            $pool = new SimplePool($poolConfig, null, $config);
            $server = \LaravelFly\Fly::getServer();
            $server->echoOnce(
                "database connection $name uses default connection pool.",
                'NOTE', true
            );
        }

        $pool->initPool($name, $this, $dispatcher);

        return $pool;

    }

    function initConnections($configs,$driver=null)
    {

        $this->connections[WORKER_COROUTINE_ID] = [];

        $dispatcher = \LaravelFly\Fly::getServer()->getDispatcher();

        foreach ($configs as $name => $config) {
            if (is_array($config)) {

                try {

                    $this->pools[$name] = $this->getPool($name,$config['driver']?? $driver, $config, $dispatcher);

                } catch (\Exception $e) {

                    $server = \LaravelFly\Fly::getServer();

                    $server->echoOnce(
                        "something wrong when making connection pool for connection '$name', \n  please check config/databas.php.\n  Maybe you need to comment out unused configs in 'connections'",
                        'WARN', true
                    );

                    // throw $e;
                }
            }
        }

        $event = Container::getInstance()->make('events');

        $event->listen('request.corinit', function ($cid) {
            $this->connections[$cid] = [];
        });
        $event->listen('request.corunset', function ($cid) {
            $this->putBack($cid);
            unset($this->connections[$cid]);
        });
        $event->listen('usercor.init', function ($parentId, $childId) {
            $this->connections[$childId] = [];
        });

        $event->listen('usercor.unset', function ($childId) {
            $this->putBack($childId);
            unset($this->connections[$childId]);

        });
        $event->listen('usercor.unset2', function ($parentId, $childId) {
            $this->putBack($childId);
            unset($this->connections[$childId]);
        });

    }

    function putBack($cid)
    {
        foreach ($this->connections[$cid] as $name => $conn) {
            // for Illuminate/Database  no worry about disconnected connections, because laravel will reconnect it when using it
            $this->pools[$name]->return($conn);
        }
    }

}