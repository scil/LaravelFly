<?php
/**
 * User: scil
 * Date: 2019/9/25
 * Time: 9:32
 */

namespace LaravelFly\Map\Illuminate\Database\Pool;

use LaravelFly\Map\Illuminate\Database\DatabaseManager;
use LaravelFly\Map\Illuminate\Redis\RedisManager;

use Swoole\Coroutine\Channel;
use Smf\ConnectionPool\ConnectionPool as SmfOfficialPool;
use Smf\ConnectionPool\Connectors\ConnectorInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\GenericEvent;

class SmfPool extends SmfOfficialPool
{
    use Pool {
        initPool as _initPool;
    }

    function initPool($name, $db,EventDispatcher $dispatcher )
    {
        $this->_initPool($name, $db,$dispatcher);

        $this->init();

        $dispatcher->addListener('worker.stopped', function (GenericEvent $event) {
            $this->close();
        });


    }

    /**
     * disable go(). cor is not allowed during OnWorkerStart
     * @return bool
     */
    public function init(): bool
    {
        if ($this->initialized) {
            return false;
        }
        $this->initialized = true;
        $this->pool = new Channel($this->maxActive);
        $this->balancerTimerId = $this->startBalanceTimer($this->idleCheckInterval);
        // hack
//        go(function () {
            for ($i = 0; $i < $this->minActive; $i++) {
                $connection = $this->createConnection();
                $ret = $this->pool->push($connection, static::CHANNEL_TIMEOUT);
                if ($ret === false) {
                    $this->removeConnection($connection);
                }
            }
//        });
        return true;
    }
    protected function createConnection()
    {
        $this->connectionCount++;

        // - $connection = $this->connector->connect($this->connectionConfig);
        // +
        $connection = $this->dbmgr->makeConnectionForPool($this->name);


        $connection->{static::KEY_LAST_ACTIVE_TIME} = time();
        return $connection;
    }

}