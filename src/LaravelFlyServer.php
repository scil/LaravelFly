<?php


class LaravelFlyServer
{
    /**
     * @var LaravelFlyServer
     */
    protected static $instance;

    protected $laravelDir;

    /**
     * @var swoole_http_server
     */
    public $swoole_http_server;

    /**
     * @var \LaravelFly\Application
     */
    protected $app;
    /**
     * @var string
     */
    protected $kernelClass;

    /**
     * @var \LaravelFly\Kernel
     */
    protected $kernel;

    public function __construct($laravelDir, $options, $kernelClass = '\App\Http\Kernel')
    {

        $this->laravelDir = realpath($laravelDir);

        $this->kernelClass = $kernelClass;

        if (!isset($options['pid_file'])) {
            $options['pid_file'] = $this->laravelDir . '/vendor/bin/laravelfly.pid';
        }

        $this->swoole_http_server = $server = new \swoole_http_server($options['listen_ip'], $options['listen_port']);

        $server->set($options);

        $server->on('WorkerStart', array($this, 'onWorkerStart'));

        $server->on('request', array($this, 'onRequest'));

        return $this;


    }

    public static function getInstance($laravelDir, $options)
    {
        if (!self::$instance) {
            try {
                self::$instance = new static($laravelDir, $options);
            } catch (\Throwable $e) {
                die('[FAILED] ' . $e->getMessage() . PHP_EOL);
            }
        }
        return self::$instance;
    }

    public function start()
    {
        try {
            $this->swoole_http_server->start();
            echo '[INFO] server start', PHP_EOL;
        } catch (\Throwable $e) {
            die('[FAILED] ' . $e->getMessage() . PHP_EOL);
        }

        $this->initSthWhenServerStart();
    }

    /**
     * Do sth. that is done in all of the Laravel requests.
     */
    protected function initSthWhenServerStart()
    {
        // removed from Illuminate\Foundation\Http\Kernel::handle
        \Illuminate\Http\Request::enableHttpMethodParameterOverride();
    }

    public function onWorkerStart()
    {
        $this->app = $app = LARAVELFLY_GREEDY ?
            new \LaravelFly\Greedy\Application($this->laravelDir) :
            new \LaravelFly\Application($this->laravelDir);

        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            LARAVELFLY_KERNEL
        );
        //todo is it needed
        $app->singleton(
            \Illuminate\Contracts\Console\Kernel::class,
            \App\Console\Kernel::class
        );
        $app->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $this->kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

        $this->bootstrapOnWorkerStart();

    }

    protected function bootstrapOnWorkerStart()
    {

        /**
         * instance a fake request
         * new UrlGenerator need app['request']
         * see: Illuminate\Routing\RoutingServiceProvider::registerUrlGenerator
         *
         * no worry about it's fake, because
         * app['url']->request will update when app['request'] changes, as
         * there is "$app->rebinding( 'request',...)"
         */
        $this->app->instance('request', \Illuminate\Http\Request::createFromBase(new \Symfony\Component\HttpFoundation\Request()));

        try {
            $this->kernel->bootstrap();
        } catch (\Throwable $e) {
            echo $e;
            $this->swoole_http_server->shutdown();
        }

    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

        // global vars used by: Symfony\Component\HttpFoundation\Request::createFromGlobals()
        // this static method is alse used by Illuminate\Auth\Guard
        $this->setGlobal($request);

        // according to : Illuminate\Http\Request::capture
        /**
         * @var Illuminate\Http\Request
         */
        $laravel_request = \Illuminate\Http\Request::createFromBase(\Symfony\Component\HttpFoundation\Request::createFromGlobals());

        // see: Illuminate\Foundation\Http\Kernel::handle($request)
        /**
         * @var Illuminate\Http\Response
         */
        $laravel_response = $this->kernel->handle($laravel_request);


        //  once there were errors saying 'http_onReceive: connection[...] is closed' which make worker restart
        // now they are useless
        // if (!$this->swoole_http_server->exist($response->fd)) {
        // return;
        // }

        foreach ($laravel_response->headers->allPreserveCase() as $name => $values) {
            foreach ($values as $value) {
                $response->header($name, $value);
            }
        }

        foreach ($laravel_response->headers->getCookies() as $cookie) {
            $response->cookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
        }

        $response->status($laravel_response->getStatusCode());

        // gzip use nginx
        // $response->gzip(1);

        $response->end($laravel_response->getContent());


        $this->kernel->terminate($laravel_request, $laravel_response);


        $this->app->restoreAfterRequest();

    }

    // copied from Swoole Framework
    // https://github.com/matyhtf/framework/libs/Swoole/Request.php
    // https://github.com/swoole/framework/libs/Swoole/Http/ExtServer.php
    protected function setGlobal($request)
    {
        if (isset($request->get)) {
            $_GET = $request->get;
        } else {
            $_GET = array();
        }
        if (isset($request->post)) {
            $_POST = $request->post;
        } else {
            $_POST = array();
        }
        if (isset($request->files)) {
            $_FILES = $request->files;
        } else {
            $_FILES = array();
        }
        if (isset($request->cookie)) {
            $_COOKIE = $request->cookie;
        } else {
            $_COOKIE = array();
        }
        if (isset($request->server)) {
            $_SERVER = $request->server;
        } else {
            $_SERVER = array();
        }
        //todo: necessary?
        foreach ($_SERVER as $key => $value) {
            $_SERVER[strtoupper($key)] = $value;
            unset($_SERVER[$key]);
        }
        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
        $_SERVER['REQUEST_URI'] = $request->server['request_uri'];

        foreach ($request->header as $key => $value) {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$_key] = $value;
        }
        $_SERVER['REMOTE_ADDR'] = $request->server['remote_addr'];
    }
}

