<?php

namespace LaravelFly\Server;

class WebSocketServer extends Base implements ServerInterface
{
    /**
     * @var \swoole_websocket_server
     */
    var $server;

    /**
     * the handler exists with server, all workers share this one handler
     * it's not like $this->app or $this->kernel
     *
     * @var \LaravelFly\WebSocketHandler\WebSocketHandlerInterface
     */
    var $handler;

    public function __construct(array $options)
    {

        parent::__construct($options);

        $options['listen_ip'] = '0.0.0.0';

        $this->server = $server = new \swoole_websocket_server($options['listen_ip'], $options['listen_port']);

        $server->set($options);
    }

    public function setListeners()
    {
        $this->server->on('open', array($this, 'onOpen'));
        $this->server->on('message', array($this, 'onMessage'));
    }

    function onOpen(\swoole_websocket_server $svr, \swoole_http_request $req)
    {
        var_dump('onopen');
        var_dump($req);
    }

    function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
    }

}