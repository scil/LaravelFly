<?php

namespace LaravelFly\Server\Traits;


use LaravelFly\Map\IlluminateBase\Request;
use Symfony\Component\EventDispatcher\GenericEvent;

Trait Laravel
{

    /**
     * For APP_TYPE=='worker', an laravel application instance living always with a worker, not the server.
     *
     * In Mode Map, it can't be made living always with the server,
     * because most of Coroutine-Friendly Services are made only by \Co::getUid()
     * without using swoole_server::$worker_id, they can not distinguish coroutines in different workers.
     *
     * @var \LaravelFly\Map\Application|\LaravelFly\Backup\Application
     */
    protected $app;

    /**
     * @var \LaravelFly\Map\IlluminateBase\Request;
     */
    protected $request;

    /**
     * An laravel kernel instance living always with a worker.
     *
     * @var \LaravelFly\Map\Kernel|\LaravelFly\Backup\Kernel
     */
    protected $kernel;

    /**
     * @return \LaravelFly\Map\Application|\LaravelFly\Backup\Application
     */
    public function getApp()
    {
        return $this->app;
    }

    public function _makeLaravelApp()
    {

        /** @var $app \LaravelFly\Map\Application|\LaravelFly\Backup\Application */
        $this->app = $app = new $this->appClass($this->root);

        /** @var \LaravelFly\Server\ServerInterface|\LaravelFly\Server\HttpServer|\LaravelFly\Server\FpmHttpServer $this */
        $app->setServer($this);

        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            $this->kernelClass
        );
        $app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );
        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $this->kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

        return $app;

    }

    protected function _makeRequest()
    {

        // if (LARAVELFLY_SERVICES['request']) {

            $this->request = new \LaravelFly\Map\IlluminateBase\Request();
            $this->app->instance('request', $this->request);
            return;
        // }

        /**
         * instance a fake request then bootstrap
         *
         * new UrlGenerator need a request.
         * In Mode Backup, no worry about it's fake, because
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
        // $this->app->instance('request', \Illuminate\Http\Request::createFromBase(new \Symfony\Component\HttpFoundation\Request()));
        // as a fake request, createFromBase is useless
        $this->app->instance('request', new \Illuminate\Http\Request());

        // todo
        // the fake request is useless, but harmless too
        // $this->app->forgetInstance('request');


    }

    public function startLaravel(\swoole_http_server $server = null, $worker_id = null)
    {
        $this->_makeLaravelApp();

        $this->_makeRequest();

        try {
            $this->kernel->bootstrap();
        } catch (\Swoole\ExitException $e) {
            echo <<<ABC
[FLY EXIT] exit() or die() executes onWorker, server will die.
Exit status is:
  {$e->getStatus()}
Trace string is:
{$e->getTraceAsString()}
ABC;
            $server && $server->shutdown();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            echo "\n[LARAVEL BOOTSTRAP ERROR] $msg\n";
            echo $e->getTraceAsString();
            $server && $server->shutdown();
        }


        if ($worker_id)
            $this->echo("event laravel.ready in id " . $worker_id);
        else
            $this->echo("event laravel.ready in pid " . getmypid());

        // the 'request' here is different form FpmHttpServer
        $event = new GenericEvent(null, ['server' => $this, 'app' => $this->app, 'request' => null]);
        $this->dispatcher->dispatch('laravel.ready', $event);

    }

    /**
     * convert swoole request info to php global vars
     *
     * only for Mode One
     *
     * @param \swoole_http_request $request
     * @see https://github.com/matyhtf/framework/blob/master/libs/Swoole/Request.php setGlobal()
     */
    protected function setGlobal($request)
    {
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_FILES = $request->files ?? [];
        $_COOKIE = $request->cookie ?? [];

        $_SERVER = array();
        foreach ($request->server as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
        }

        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);

        foreach ($request->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$_key] = $value;
        }
    }

    /**
     * @deprecated
     *
     * @param \swoole_http_request $swoole_request
     * @return \Illuminate\Http\Request
     *
     * from: Illuminate\Http\Request\createFromBase
     */
    public function createLaravelRequest(\swoole_http_request $swoole_request)
    {
        $server = [];

        foreach ($swoole_request->server as $key => $value) {
            $server[strtoupper($key)] = $value;
        }

        foreach ($swoole_request->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $server[$_key] = $value;
        }


        $request = new \Illuminate\Http\Request(
            $swoole_request->get ?? [],
            $swoole_request->post ?? [],
            [],
            $swoole_request->cookie ?? [],
            $swoole_request->files ?? [],
            $server,
            $swoole_request->rawContent() ?: null
        );

        /*
         *
         * from: Illuminate\Http\Request\createFromBase
         *      $newRequest->request = $newRequest->getInputSource();
         */
        (function () {
            /**
             * @var $this Request
             */
            $this->request = $this->getInputSource();
            // todo filterFiles
        })->call($request);

        return $request;

    }

    /**
     * produce swoole response from laravel response
     *
     * @param \swoole_http_response $response
     * @param $laravel_response
     */
    protected function swooleResponse(\swoole_http_response $response, \Symfony\Component\HttpFoundation\Response $laravel_response): void
    {
        foreach ($laravel_response->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        /** @var  \Symfony\Component\HttpFoundation\Cookie $cookie */
        foreach ($laravel_response->headers->getCookies() as $cookie) {
            $response->cookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
        }

        $response->status($laravel_response->getStatusCode());

        $response->end($laravel_response->getContent());
    }
}

