<?php

namespace LaravelFly\Server;


use LaravelFly\Tinker;

class FpmHttpServer implements ServerInterface
{
    use Common;

    /**
     * @var \swoole_http_server
     */
    var $server;


    public function __construct(array $options)
    {

        $this->parseOptions($options);

        $this->kernelClass = null;


        $this->server = $server = new \swoole_http_server($options['listen_ip'], $options['listen_port']);

        $server->set($options);

        $this->setListeners();

    }

    function setListeners()
    {
        $this->server->on('WorkerStart', array($this, 'onWorkerStart'));

        $this->server->on('request', array($this, 'onRequest'));
    }

    public function onWorkerStart()
    {
        $this->initTinker();
    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

        $app = new $this->appClass($this->root);

        $this->withTinker($app);

        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \App\Http\Kernel::class
        );
        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );


        $this->setGlobal($request);

        $laravel_request = \Illuminate\Http\Request::createFromBase(\Symfony\Component\HttpFoundation\Request::createFromGlobals());

        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

        $laravel_response = $kernel->handle(
        // $request = \Illuminate\Http\Request::capture()
            $laravel_request
        );

        $this->swooleResponse($response, $laravel_response);

        $kernel->terminate($laravel_request, $laravel_response);
    }

}