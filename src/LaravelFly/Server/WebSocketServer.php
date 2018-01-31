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
        var_dump($this->req === $req);
    }

    function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
    }

    /**
     * @param swoole_http_request $request
     * @param swoole_http_response $response
     * @return bool
     *
     * from: https://wiki.swoole.com/wiki/page/409.html
     */
    function onHandShake(swoole_http_request $request, swoole_http_response $response)
    {
        var_dump('onhand');
        var_dump($request);
        $this->req=$request;
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }


        $key = base64_encode(sha1(
            $secWebSocketKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
            true
        ));

        $response->header('Upgrade', 'websocket');
        $response->header('Connection', 'Upgrade');
        $response->header('Sec-WebSocket-Accept', $key);
        $response->header('Sec-WebSocket-Version', '13');

        // WebSocket connection to 'ws://127.0.0.1:9502/'
        // failed: Error during WebSocket handshake:
        // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
        if (isset($request->header['sec-websocket-protocol'])) {
            $response->header('Sec-WebSocket-Protocol', $request->header['sec-websocket-protocol']);
        }

        $response->status(101);
        $response->end();
        return true;
    }

}