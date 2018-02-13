<?php

namespace LaravelFly\Server;

class WebSocketServer implements ServerInterface
{
    use Common{
    }
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

        $this->parseOptions($options);

        $options['listen_ip'] = '0.0.0.0';

        $this->server = $server = new \swoole_websocket_server($options['listen_ip'], $options['listen_port']);

        $server->set($options);
    }

    public function setListeners()
    {
        $this->server->on('open', array($this, 'onOpen'));
        $this->server->on('message', array($this, 'onMessage'));
        $this->server->on('close', array($this, 'onClose'));
    }

    function onOpen(\swoole_websocket_server $svr, \swoole_http_request $req)
    {
        var_dump($req);
    }


}