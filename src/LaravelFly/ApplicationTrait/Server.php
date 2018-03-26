<?php

namespace LaravelFly\ApplicationTrait;

use Illuminate\Events\EventServiceProvider;
//use LaravelFly\Routing\RoutingServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use LaravelFly\Simple\ProviderRepositoryInRequest;

trait Server
{

    /**
     * @var \LaravelFly\Server|\LaravelFly\Server\HttpServer|\LaravelFly\Server\FpmHttpServer
     */
    protected $server;

    /**
     * @return \LaravelFly\Server|\LaravelFly\Server\FpmHttpServer|\LaravelFly\Server\HttpServer
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param \LaravelFly\Server|\LaravelFly\Server\FpmHttpServer|\LaravelFly\Server\HttpServer $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    public function isDownForMaintenance()
    {
        echo 'is down:',$this->server->memory['isDown']->get(),"\n";
        return $this->server->memory['isDown']->get();
    }

}
