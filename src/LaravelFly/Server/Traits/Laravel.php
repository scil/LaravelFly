<?php

namespace LaravelFly\Server\Traits;

use Symfony\Component\EventDispatcher\GenericEvent;
use Illuminate\Support\Facades\Facade;

Trait Laravel
{

    /**
     * For APP_TYPE=='worker', an laravel application instance living always with a worker, not the server.
     *
     * In Mode Map, it can't be made living always with the server,
     * because most of Coroutine-Friendly Services are made only by \Co::getUid()
     * without using swoole_server::$worker_id, they can not distinguish coroutines in different workers.
     *
     * @var \LaravelFly\Map\Application|\LaravelFly\Simple\Application
     */
    protected $app;

    /**
     * An laravel kernel instance living always with a worker.
     *
     * @var \LaravelFly\Map\Kernel|\LaravelFly\Simple\Kernel
     */
    protected $kernel;

    /**
     * @return \LaravelFly\Map\Application|\LaravelFly\Simple\Application
     */
    public function getApp()
    {
        return $this->app;
    }

    public function _makeLaravelApp()
    {

        /** @var $app \LaravelFly\Map\Application|\LaravelFly\Simple\Application */
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

    public function startLaravel(\swoole_http_server $server = null)
    {
        $app = $this->_makeLaravelApp();

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
            $msg=$e->getMessage();
            echo "[FLY ERROR] bootstrap: $msg\n";
            $server && $server->shutdown();
        }

        // the fake request is useless, but harmless too
        // $this->app->forgetInstance('request');
        
        Facade::clearResolvedInstance('request');
        //'url' has made? when? \Illuminate\Routing\RoutingServiceProvider
        Facade::clearResolvedInstance('url');

        $this->echo("event laravel.ready with $this->appClass in pid ".getmypid());

        // the 'request' here is different form FpmHttpServer
        $event = new GenericEvent(null, ['server' => $this, 'app' => $app, 'request' => null]);
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

        // gzip use nginx
        // $response->gzip(1);

        $response->end($laravel_response->getContent());
    }
}

