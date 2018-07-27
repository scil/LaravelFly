<?php
namespace LaravelFly\Http\Controllers;

class InfoController extends BaseController
{
    /**
     * @var \LaravelFly\Map\Application | \LaravelFly\Simple\Application
     */
    var $app;
    var $server;
    var $swoole;

    function __construct()
    {
        $this->app= app();
        $this->server = $this->app->getServer();
        $this->swoole = $this->server->getSwooleServer();
    }

    function index(){

        $server = $this->server;
        $swoole = $this->swoole;


        $info = $swoole->setting + [
            'PID'=> $swoole->master_pid,
            'current worker pid'=> $swoole->worker_pid,
            'current worker id'=> $swoole->worker_id,

        ];

        return $info;
    }

}