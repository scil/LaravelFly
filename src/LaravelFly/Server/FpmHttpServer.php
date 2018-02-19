<?php

namespace LaravelFly\Server;


use Symfony\Component\EventDispatcher\GenericEvent;

class FpmHttpServer implements ServerInterface
{
    /**
     * every request has an app
     */
    const APP_TYPE = 'request';

    use Common{
        create as _create;
    }

    function setListeners()
    {
        $this->server->on('WorkerStart', array($this, 'onWorkerStart'));

        $this->server->on('request', array($this, 'onRequest'));
    }

    public function onWorkerStart(\swoole_server $server, int $worker_id)
    {
        $event = new GenericEvent(null, ['server' => $this, 'workerid' => $worker_id]);
        $this->dispatcher->dispatch('worker.started', $event);
    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

        $app = new $this->appClass($this->root);

        $event = new GenericEvent(null,['server'=>$this,'request'=>$request,'app'=>$app]);
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