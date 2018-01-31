<?php

namespace LaravelFly\Server;

class WebSocketServer extends Base implements ServerInterface
{
    /**
     * @var \swoole_websocket_server
     */
    var $server;


    public function __construct(array $options)
    {

        parent::__construct($options);

        $this->server = $server = new \swoole_websocket_server($options['listen_ip'], $options['listen_port']);

        $server->set($options);
    }

    public function setListeners()
    {
        $this->server->on('message', array($this, 'onMessage'));
    }

    function onMessage(\swoole_websocket_server $server,\swoole_websocket_frame $frame)
    {
    }


}