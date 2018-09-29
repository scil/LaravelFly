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
            $this->pools[$name]->put($conn);
        }
    }

}