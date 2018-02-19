<?php

namespace LaravelFly\Server;


use Symfony\Component\EventDispatcher\GenericEvent;

class FpmHttpServer implements ServerInterface
{
    /**
     * every request has an app
     */
    const APP_TYPE = 'request';

    use Common {
        create as _create;
    }

    function setListeners()
    {
        $this->swoole->on('WorkerStart', array($this, 'onWorkerStart'));

        $this->swoole->on('request', array($this, 'onRequest'));
    }


    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

        $app = new $this->appClass($this->root);

        $event = new GenericEvent(null, ['server' => $this, 'app' => $app, 'request' => $request]);
        $this->dispatcher->dispatch('app.created', $event);

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