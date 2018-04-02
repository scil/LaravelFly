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
     * @var \LaravelFly\Server\ServerInterface|\LaravelFly\Server\HttpServer|\LaravelFly\Server\FpmHttpServer
     */
    protected $server;

    /**
     * @return \LaravelFly\Server\ServerInterface|\LaravelFly\Server\FpmHttpServer|\LaravelFly\Server\HttpServer
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param \LaravelFly\Server\ServerInterface|\LaravelFly\Server\FpmHttpServer|\LaravelFly\Server\HttpServer $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    public function isDownForMaintenance()
    {
        return $this->server->getMemory('isDown')->get();
    }

}
