<?php


class LaravelFlyServer
{
    /**
     * @var LaravelFlyServer
     */
    protected static $instance;

    /**
     * @var string where laravel app located
     */
    protected $root;

    /**
     * @var swoole_http_server
     */
    public $swoole_http_server;

    /**
     * @var \LaravelFly\Greedy\Application
     */
    protected $workerApplication;
    /**
     * @var string
     */
    protected $kernelClass;

    /**
     * @var \LaravelFly\Greedy\Kernel
     */
    protected $workerKernel;

    public function __construct($options, $kernelClass = '\App\Http\Kernel')
    {

        $this->root = realpath(__DIR__ . '/../../../..');
        if (!(is_dir($this->root) && is_file($this->root . '/bootstrap/app.php'))) {
            die("This doc root is not for a Laravel app: {$this->root} ");
        }

        $this->kernelClass = $kernelClass;

        if (!isset($options['pid_file'])) {
            $options['pid_file'] = $this->root . '/bootstrap/laravelfly.pid' . $options['listen_port'];
        } else {
            $options['pid_file'] .= $options['listen_port'];
        }

        $this->swoole_http_server = $server = new \swoole_http_server($options['listen_ip'], $options['listen_port']);

        $server->set($options);

        $server->on('WorkerStart', array($this, 'onWorkerStart'));

        $server->on('request', array($this, 'onRequest'));

        return $this;


    }

    public static function getInstance($options)
    {
        if (!self::$instance) {
            try {
                self::$instance = new static($options);
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
//        echo "[INFO] worker start/reload. master pid:{$this->swoole_http_server->master_pid}; manager pid:{$this->swoole_http_server->manager_pid}", PHP_EOL;

        $appClass = '\LaravelFly\\' . LARAVELFLY_MODE . '\Application';

        $this->workerApplication =  new $appClass($this->root);

        $this->workerApplication->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            LARAVELFLY_KERNEL
        );
        $this->workerApplication->singleton(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            \App\Exceptions\Handler::class
        );

        $this->workerKernel = $this->workerApplication->make(\Illuminate\Contracts\Http\Kernel::class);

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
        $this->workerApplication->instance('request', \Illuminate\Http\Request::createFromBase(new \Symfony\Component\HttpFoundation\Request()));

        try {
            $this->workerKernel->bootstrap();
        } catch (\Throwable $e) {
            echo $e;
            $this->swoole_http_server->shutdown();
        }

    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

        if (LARAVELFLY_MODE == 'Coroutine') {

            $laravel_request = (new \LaravelFly\Coroutine\Illuminate\Request())->createFromSwoole($request);

            $requestApp = clone $this->workerApplication;
            $laravel_response= $this->workerKernel->handle($laravel_request);

        } else {

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
            $laravel_response = $this->workerKernel->handle($laravel_request);
        }


        // once there were errors saying 'http_onReceive: connection[...] is closed' which make worker restart
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

        $this->workerKernel->terminate($laravel_request, $laravel_response);

        if (LARAVELFLY_MODE == 'Coroutine') {

            $this->workerApplication->delRequestApplication(\Swoole\Coroutine::getuid());

        } else {

            $this->workerApplication->restoreAfterRequest();

        }
    }

    // copied from Swoole Framework
    // https://github.com/matyhtf/framework/libs/Swoole/Request.php
    // https://github.com/swoole/framework/libs/Swoole/Http/ExtServer.php
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
}

