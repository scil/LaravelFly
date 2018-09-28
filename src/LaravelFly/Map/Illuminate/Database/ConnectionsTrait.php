<?php

namespace LaravelFly\Map\Illuminate\Database;

use Illuminate\Container\Container;

trait ConnectionsTrait
{
    /**
     * @var Pool[] $pools
     */
    protected $pools = [];


    function initConnections($configs)
    {

        $this->connections[WORKER_COROUTINE_ID] = [];

        $defaultPoolsize = \LaravelFly\Fly::getServer()->getConfig('poolsize');

        foreach ($configs as $name => $config) {
            if (is_array($config))
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
}