<?php

namespace LaravelFly\Server;

use LaravelFly\Server\Event\WorkerStarted;
use Symfony\Component\EventDispatcher\GenericEvent;

class HttpServer implements ServerInterface
{
    use Common {
        start as _start;
    }

    /**
     * every worker has an app
     */
    const APP_TYPE = 'worker';

    function setListeners()
    {
        $this->swoole->on('WorkerStart', array($this, 'onWorkerStart'));

        $this->swoole->on('WorkerStop', array($this, 'onWorkerStop'));

        if (LARAVELFLY_MODE == 'Map') {
            $this->swoole->on('request', array($this, 'onMapRequest'));
        } else {
            $this->swoole->on('request', array($this, 'onRequest'));
        }
    }

    public function start()
    {
        $this->_start();
        \Illuminate\Http\Request::enableHttpMethodParameterOverride();
    }

    public function onWorkerStart(\swoole_server $server, int $worker_id)
    {

        $this->workerStartHead($server, $worker_id);

        $this->startLaravel();

        /**
         * instance a fake request then bootstrap
         *
         * new UrlGenerator need a request.
         * In Mode Simple, no worry about it's fake, because
         * app['url']->request will update when app['request'] changes, as rebinding is used
         * <code>
         * <?php
         * $url = new UrlGenerator(
         *  $routes, $app->rebinding(
         *      'request', $this->requestRebinder()
         *  )
         * );
         * ?>
         *  "$app->rebinding( 'request',...)"
         * </code>
         * @see  \Illuminate\Routing\RoutingServiceProvider::registerUrlGenerator()
         *
         */
        $this->app->instance('request', \Illuminate\Http\Request::createFromBase(new \Symfony\Component\HttpFoundation\Request()));

        try {
            $this->kernel->bootstrap();
        } catch (\Throwable $e) {
            echo $e;
            $server->shutdown();
        }

        $this->app->forgetInstance('request');

        // disable app dispatcher
        // event('worker.ready', [$this]);
        $event = new GenericEvent(null, ['server' => $this, 'workerid' => $worker_id, 'app' => $this->app]);
        $this->dispatcher->dispatch('worker.ready', $event);

        printf("[INFO] pid %u: worker %u ready\n", getmypid(), $worker_id);

         if ($worker_id == 0) {
             $this->worker0StartTail($server,['downDir'=>$this->app->storagePath() . '/framework/']);
         }
    }

    /**
     * handle request for Mode Simple or Greedy
     *
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     *
     */
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

        /**
         * @see \Symfony\Component\HttpFoundation\Request::createFromGlobals() use global vars, and
         * this static method is alse used by {@link \Illuminate\Auth\SessionGuard }
         */
        $this->setGlobal($request);

        /**
         * @var \Illuminate\Http\Request
         * @see \Illuminate\Http\Request::capture
         */
        $laravel_request = \Illuminate\Http\Request::createFromBase(\Symfony\Component\HttpFoundation\Request::createFromGlobals());


        /**
         * @var \Illuminate\Http\Response
         * @see \Illuminate\Foundation\Http\Kernel::handle
         */
        $laravel_response = $this->kernel->handle($laravel_request);


        $this->swooleResponse($response, $laravel_response);


        $this->kernel->terminate($laravel_request, $laravel_response);

        $this->app->restoreAfterRequest();


    }

    public function onMapRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

        $laravel_request = (new \LaravelFly\Map\IlluminateBase\Request())->createFromSwoole($request);

        $cid = \co::getUid();

        $this->app->initForRequestCorontine($cid);

        // why use clone for kernel, because there's a \App\Http\Kernel which is controlled by users
        $requestKernel = clone $this->kernel;

        $laravel_response = $requestKernel->handle($laravel_request);


        $this->swooleResponse($response, $laravel_response);


        $requestKernel->terminate($laravel_request, $laravel_response);

        $this->app->unsetForRequestCorontine($cid);

    }


}