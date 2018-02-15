<?php

namespace LaravelFly\Server;


class HttpServer implements ServerInterface
{
    use Common{
        start as _start;
    }
    /**
     * @var \swoole_http_server
     */
    var $server;


    public function __construct(array $options)
    {

        $this->parseOptions($options);

        $this->server = $server = new \swoole_http_server($options['listen_ip'], $options['listen_port']);

        $server->set($options);

        $this->setListeners();
    }

    function setListeners()
    {
        $this->server->on('WorkerStart', array($this, 'onWorkerStart'));

        $this->server->on('request', array($this, 'onRequest'));
    }

    public function start()
    {
        $this->_start();
        \Illuminate\Http\Request::enableHttpMethodParameterOverride();
    }

    /**
     * instance a fake request then bootstrap
     *
     * new UrlGenerator need a request.
     * In Mode One, no worry about it's fake, because
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
    public function onWorkerStart(\swoole_server $server, int $worker_id)
    {
        opcache_reset();

        $this->startLaravel();

        $this->app->instance('request', \Illuminate\Http\Request::createFromBase(new \Symfony\Component\HttpFoundation\Request()));

        try {
            $this->kernel->bootstrap();
        } catch (\Throwable $e) {
            echo $e;
            $this->server->shutdown();
        }

        $this->app->forgetInstance('request');

    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

        if (LARAVELFLY_MODE == 'Dict') {

            $cid = \Swoole\Coroutine::getuid();

            $laravel_request = (new \LaravelFly\Dict\IlluminateBase\Request())->createFromSwoole($request);

            $this->app->initForCorontine($cid);

            // why use clone for kernel, because there's a \App\Http\Kernel
            $requestKernel = clone $this->kernel;

            $laravel_response = $requestKernel->handle($laravel_request);

        } else {

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
        }

        $this->swooleResponse($response, $laravel_response);


        if (LARAVELFLY_MODE == 'Dict') {

            $requestKernel->terminate($laravel_request, $laravel_response);

            $this->app->delForCoroutine($cid);

        } else {

            $this->kernel->terminate($laravel_request, $laravel_response);

            $this->app->restoreAfterRequest();

        }
    }


}